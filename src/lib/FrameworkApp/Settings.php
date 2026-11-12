<?php

namespace FrameworkApp;

class Settings extends \Routing_Parent implements \Routing_Interface {

	private array $currentUser = [];
	private bool $need_change_password = false;

	private string $successText = '';
	private string $errorText = '';

	private string $passwordSuccessText = '';
	private string $passwordErrorText = '';


	public function Run() {
		$this->check_auth();

		$this->currentUser = \Sessions::currentUser();
		if (!$this->currentUser['telegram_id']) $this->currentUser['telegram_id'] = '';

		$this->processRequest();

		$this->template = new \Template('Settings');
		$this->print_header();
		echo '<div class="row d-flex justify-content-center">';

		$this->need_change_password = false;
		if (\Config::getInstance()->app_signin_active && $this->currentUser['flags'] & \Users::FLAGS_NEED_CHANGE_PASSWORD) {
			$this->need_change_password = true;
		}
		if (!$this->need_change_password) {
			$this->userProfileCard();
		}
		if (\Config::getInstance()->app_signin_active) {
			$this->userPasswordCard();
		}

		if (!$this->need_change_password && \Config::getInstance()->app_signin_active && isset($this->currentUser['password']) && $this->currentUser['password']) {
			$this->userTwoFactorCard();
		}

		echo '</div>';
	}

	protected function handleGetData(array $data) {
		if (!isset($data)) return;
		if (!empty($data['confirm_email'])) {
			$this->confirmPendingEmail(strval($data['confirm_email']));
		}
	}

	private function confirmPendingEmail(string $token): void {
		if ($token === '' || !$this->currentUser || empty($this->currentUser['id'])) {
			return;
		}
		$cache = new \Cache('pending_email_'.$this->currentUser['id'], 'default', true);
		if (!$cache->isValid()) {
			$this->errorText = \T::Framework_SignUp_InvalidOrExpiredToken();
			return;
		}
		$pending = json_decode(strval($cache->get()), true);
		if (
			!is_array($pending)
			|| empty($pending['email'])
			|| empty($pending['token'])
			|| !hash_equals(strval($pending['token']), $token)
			|| ($pending['exp'] ?? 0) < time()
		) {
			$cache->remove();
			$this->errorText = \T::Framework_SignUp_InvalidOrExpiredToken();
			return;
		}
		$email = strval($pending['email']);
		$existingUser = \Users::get(['email' => $email], sql_pholder(' AND id <> ?', $this->currentUser['id']));
		if ($existingUser) {
			$cache->remove();
			$this->errorText = \T::Framework_Settings_EmailAlreadyInUse();
			return;
		}
		$old_user = \Users::get(['id' => $this->currentUser['id']]);
		\Users::save([
			'id' => $this->currentUser['id'],
			'email' => $email,
			'email_verification_token' => '',
			'update_time' => time(),
			'_mode' => \DB\Common::CSMODE_UPDATE,
		]);
		$cache->remove();
		$new_user = \Users::get(['id' => $this->currentUser['id']]);
		\Logs::update_log(\Users::LOGS_OBJECT, $this->currentUser['id'], $old_user, $new_user, [
			'ip' => \Sessions::client_ip(),
			'_save_fields' => ['id'],
		]);
		$this->currentUser = $new_user;
		$this->successText = \T::Framework_Settings_ProfileUpdatedSuccessfully();
		common_redirect('/settings/');
	}

	protected function handlePostData(array $data) {
		// Обработка POST данных
		if (!isset($data)) return;

		if (isset($data['sendTelegram'])) {
			$return = [];
			$telegramId = trim($data['telegram_id']??'');
			do {
				if (!$telegramId) {
					$return = ['status' => -1, 'error' => \T::Framework_Telegram_TelegramIdIsRequired()];
					break;
				}
				if (!\Config::getInstance()->telegram_bot_token) {
					$return = ['status' => -2, 'error' => \T::Framework_Telegram_TelegramBotTokenIsRequired()];
					break;
				}
				// Only allow testing the current user's configured telegram id
				$allowed = trim(strval($this->currentUser['telegram_id'] ?? ''));
				if ($allowed === '' || $telegramId !== $allowed) {
					$return = ['status' => -1, 'error' => \T::Framework_Errors_PermissionDenied()];
					break;
				}
				$telegramIds = explode(':', $telegramId);
				$telegram_channel_id = $telegramIds[0];
				$telegram_thread_id = 0;
				if (isset($telegramIds[1])) {
					$telegram_thread_id = $telegramIds[1];
				}
				try {
					$bot = new \TelegramBot\Api\BotApi(\Config::getInstance()->telegram_bot_token);
					if (\Config::getInstance()->telegram_proxy_host) {
						$bot->setProxy((\Config::getInstance()->telegram_proxy_host).':'.(\Config::getInstance()->telegram_proxy_port), \Config::getInstance()->telegram_proxy_socks5);
						if (\Config::getInstance()->telegram_proxy_username && \Config::getInstance()->telegram_proxy_password) {
							$bot->setCurlOption(CURLOPT_PROXYUSERPWD, \Config::getInstance()->telegram_proxy_username.':'.\Config::getInstance()->telegram_proxy_password);
						}
					}
					@$bot->sendMessage(
						$telegram_channel_id,
						'Test Message '.date('Y-m-d H:i:s',time()).'',
						'HTML', true, null, null, false,
						$telegram_thread_id
					);
					$return = ['status' => 0];
				} catch (\Exception $e) {
					$return = ['status' => -3, 'error' => $e->getMessage()];
				}
			} while (false);
			echo json_encode($return);
			exit;
		}


		if (\Config::getInstance()->app_signin_active && isset($data['changePassword']) && $data['changePassword'] == 'true') {
			$this->changePassword($data['oldPassword']??'', $data['newPassword']??'', $data['confirmNewPassword']??'');
		}
		if (isset($data['enableTwoFactor']) && $data['enableTwoFactor'] == 'true') {
			$this->enableTwoFactor($data['secret'] ?? '', $data['totp'] ?? '');
			exit;
		}
		if (isset($data['disableTwoFactor']) && $data['disableTwoFactor'] == 'true') {
			$this->disableTwoFactor($data['password'] ?? '');
			exit;
		}

		if (isset($data['changeUserProfile']) && $data['changeUserProfile'] == 'true') {
			$this->changeUserProfile($data);
		}
	}

	private function changePassword($oldPassword, $newPassword, $confirmNewPassword) {
		$oldPassword = $oldPassword??'';
		$newPassword = $newPassword??'';
		$confirmNewPassword = $confirmNewPassword??'';
		if (!$oldPassword || !$newPassword || !$confirmNewPassword) {
			$this->passwordErrorText = \T::Framework_Settings_AllFieldsAreRequired();
			return;
		}
		if ($newPassword !== $confirmNewPassword) {
			$this->passwordErrorText = \T::Framework_Settings_NewPasswordsDoNotMatch();
			return;
		}
		if (!$this->currentUser || !isset($this->currentUser['id']) || intval($this->currentUser['id']) < 1) {
			$this->passwordErrorText = \T::Framework_Settings_UserNotFound();
			return;
		}
		if (\Users::check_user_credentials($this->currentUser['email'], $oldPassword) != $this->currentUser['id']) {
			$this->passwordErrorText = \T::Framework_Settings_OldPasswordIsIncorrect();
			return;
		}
		$newPasswordHash = \Users::password_hash($newPassword);
		$params = [
			'id' => $this->currentUser['id'],
			'password' => $newPasswordHash,
			'reset_token' => '',
			'reset_token_expires' => 0,
			'flags' => $this->currentUser['flags'] & ~\Users::FLAGS_NEED_CHANGE_PASSWORD,
			'update_time' => time(),
			'_mode' => \DB\Common::CSMODE_UPDATE,
		];
		$old_user = \Users::get(['id' => $this->currentUser['id']]);
		\Users::save($params);
		$new_user = \Users::get(['id' => $this->currentUser['id']]);
		\Logs::update_log(\Users::LOGS_OBJECT, $this->currentUser['id'], $old_user, $new_user,[
			'ip' => \Sessions::client_ip(),
			'_save_fields' => ['id'],
		]);
		$_POST = [];
		$this->currentUser['flags'] = $this->currentUser['flags'] & ~\Users::FLAGS_NEED_CHANGE_PASSWORD;
		\Sessions::set_current_user($this->currentUser['id']);
		\Users::revoke_other_sessions($this->currentUser['id'], \Sessions::get_session_id());
		$this->passwordSuccessText = \T::Framework_Settings_PasswordChangedSuccessfully();
	}

	private function disableTwoFactor(string $password = '') {
		if (!\Config::getInstance()->app_signin_active) {
			echo json_encode(['error' => \T::Framework_Errors_PermissionDenied()]);
			return;
		}
		$currentUser = \Sessions::currentUser();
		if (!isset($currentUser['password']) || !$currentUser['password']) {
			echo json_encode(['error' => \T::Framework_Errors_PermissionDenied()]);
			return;
		}
		if (\Users::check_user_credentials($currentUser['email'], $password) != $currentUser['id']) {
			echo json_encode(['error' => \T::Framework_Settings_OldPasswordIsIncorrect()]);
			return;
		}
		$user = \Users::get(['id' => $currentUser['id']]);
		if (!$user || !$user['id']) {
			echo json_encode(['error' => \T::Framework_Settings_UserNotFound()]);
			return;
		}
		$user_id = \Users::save([
			'id' => $user['id'],
			'two_factor_secret' => '',
			'update_time' => time(),
			'_mode' => \DB\Common::CSMODE_UPDATE,
		]);
		if ($user_id) {
			echo json_encode(['status' => 'true']);
		} else {
			echo json_encode(['error' => \T::Framework_Settings_TwoFactor_ErrorSetup()]);
		}
	}

	private function enableTwoFactor($secret, $totp) {
		if (!\Config::getInstance()->app_signin_active) exit;
		$currentUser = \Sessions::currentUser();
		if (!isset($currentUser['password']) || !$currentUser['password']) exit;
		$secret = strval($secret ?? '');
		$totp = preg_replace('/\D+/', '', strval($totp ?? ''));
		$googleAuthenticate = new \GoogleAuthenticator();
		$check = $googleAuthenticate->checkCode($secret, $totp);
		if (!$check) {
			echo json_encode(['error' => \T::Framework_Login_InvalidTOTP()]);
			exit;
		}
		$user = \Users::get(['id' => $currentUser['id']]);
		if (!$user || !$user['id']) {
			echo json_encode(['error' => \T::Framework_Settings_UserNotFound()]);
			exit;
		}
		$user_id = \Users::save([
			'id' => $user['id'],
			'two_factor_secret' => $secret,
			'update_time' => time(),
			'_mode' => \DB\Common::CSMODE_UPDATE,
		]);
		if ($user_id) {
			echo json_encode(['status' => "true"]);
		}
		exit;
	}

	private function changeUserProfile($data) {
		$name = trim($data['name']??'');
		$surname = trim($data['surname']??'');
		$email = trim($data['email']??'');
		$telegramId = trim($data['telegram_id']??'');
		if (!$name || !$surname || !$email) {
			$this->errorText = \T::Framework_Settings_AllFieldsAreRequired();
			return;
		}
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->errorText = \T::Framework_Settings_InvalidEmailFormat();
			return;
		}
		if (!$this->currentUser || !isset($this->currentUser['id']) || intval($this->currentUser['id']) < 1) {
			$this->errorText = \T::Framework_Settings_UserNotFound();
			return;
		}
		$emailChanged = strcasecmp($email, strval($this->currentUser['email'] ?? '')) !== 0;
		if ($emailChanged) {
			$currentPassword = strval($data['currentPassword'] ?? '');
			if (!empty($this->currentUser['password'])) {
				if (\Users::check_user_credentials($this->currentUser['email'], $currentPassword) != $this->currentUser['id']) {
					$this->errorText = \T::Framework_Settings_OldPasswordIsIncorrect();
					return;
				}
			}
			// Check if email is already used by another user
			$existingUser = \Users::get(['email' => $email], sql_pholder(' AND id <> ?', $this->currentUser['id']));
			if ($existingUser) {
				$this->errorText = \T::Framework_Settings_EmailAlreadyInUse();
				return;
			}
		}

		$params = [
			'id' => $this->currentUser['id'],
			'name' => $name,
			'surname' => $surname,
			'telegram_id' => $telegramId,
			'update_time' => time(),
			'_mode' => \DB\Common::CSMODE_UPDATE,
		];
		if ($emailChanged) {
			// Do not activate new email until verified; keep current email for login
			$verifyToken = \Users::generate_verification_token();
			$params['email_verification_token'] = $verifyToken;
			$cache = new \Cache('pending_email_'.$this->currentUser['id'], 'default', true);
			$cache->update(json_encode(['email' => $email, 'token' => $verifyToken, 'exp' => time() + 86400]), 86400);
			$verifyLink = htmlspecialchars(common_app_url('/settings/?confirm_email=' . urlencode($verifyToken)), ENT_QUOTES, 'UTF-8');
			\Mail::send($email, \T::Framework_SignUp_EmailSubject(), \T::Framework_SignUp_EmailBody($verifyLink));
		}
		$old_user = \Users::get(['id' => $this->currentUser['id']]);
		\Users::save($params);
		$timezone = trim(strval($data['timezone'] ?? ''));
		if ($timezone !== '' && \UserTime::isValidTimezone($timezone)) {
			\UserTime::setTimezone($timezone);
		}
		$manual = !empty($data['timezone_manual']);
		\Sessions::set_server_cookie('timezone_manual', $manual);
		\UserTime::clearCache();
		$new_user = \Users::get(['id' => $this->currentUser['id']]);
		\Logs::update_log(\Users::LOGS_OBJECT, $this->currentUser['id'], $old_user, $new_user,[
			'ip' => \Sessions::client_ip(),
			'_save_fields' => ['id'],
		]);
		$this->successText = $emailChanged
			? \T::Framework_Login_ResendEmailSuccess()
			: \T::Framework_Settings_ProfileUpdatedSuccessfully();
		$this->currentUser = \Users::get(['id' => $this->currentUser['id']]);
	}

	private function print_header() {
		echo '<h2>'.\T::Framework_Settings_Title().'</h2>';
		echo \T::Framework_Settings_Subtitle();
	}

	private function userProfileCard() {
		?>
		<div class="card card-paddings bg-transparent col-xl-8 p-4 mt-2 mb-3">
			<div class="card-header bg-transparent"><h3><?=\T::Framework_Settings_UserProfile_Title();?></h3></div>
			<form class="card-body" method="post">
				<?= \Csrf::field(); ?>
				<?php if ($this->successText) { ?>
					<div class="alert alert-success d-flex align-items-center fade show alert-dismissible" role="alert">
						<div><?=htmlspecialchars($this->successText, ENT_QUOTES, 'UTF-8');?></div>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				<?php } ?>
				<?php if ($this->errorText) { ?>
					<div class="alert alert-danger d-flex align-items-center fade show alert-dismissible" role="alert">
						<div><?=htmlspecialchars($this->errorText, ENT_QUOTES, 'UTF-8');?></div>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				<?php } ?>
				<?php
				echo $this->template->html_input("name", $this->currentUser['name']??'', \T::Framework_Settings_UserProfile_Name(), true);
				echo $this->template->html_input("surname", $this->currentUser['surname']??'', \T::Framework_Settings_UserProfile_Surname(), true);
				echo $this->template->html_input("email", $this->currentUser['email']??'', \T::Framework_Settings_UserProfile_Email(), true, [
					'type' => 'email',
					'add_before' => '<i class="input-group-text bi bi-envelope"></i>',
				]);
				if (!empty($this->currentUser['password'])) {
					echo $this->template->html_input("currentPassword", '', \T::Framework_Settings_OldPassword(), false, [
						'type' => 'password',
						'add_before' => '<i class="input-group-text bi bi-key"></i>',
					]);
				}
				echo $this->template->html_input("telegram_id", $this->currentUser['telegram_id']??'', \T::Framework_Settings_UserProfile_TelegramId(), false, [
					'type' => 'text',
					'add_before' => '<i class="input-group-text bi bi-telegram"></i>',
					'add_after' => (\Config::getInstance()->telegram_bot_token ? '<button class="btn btn-outline-warning sendTelegramButton" type="button">Test</button>' : ''),
				]);
				echo $this->template->html_select(
					'timezone',
					\UserTime::listTimezones(),
					\UserTime::timezone(),
					\T::Framework_Settings_UserProfile_Timezone(),
					false,
					['data-container' => 'body']
				);
				?>
				<div class="mb-3 form-check">
					<input class="form-check-input" type="checkbox" value="1" id="timezone_manual" name="timezone_manual" <?= \Sessions::get_server_cookie('timezone_manual', false) ? 'checked' : '';?>>
					<label class="form-check-label" for="timezone_manual"><?=\T::Framework_Settings_UserProfile_TimezoneManual();?></label>
					<div class="form-text"><?=\T::Framework_Settings_UserProfile_TimezoneHint();?></div>
				</div>
				<div class="d-flex flex-row-reverse">
					<button type="submit" class="btn btn-primary" name="changeUserProfile" value="true"><?=\T::Framework_Settings_UserProfile_Change();?></button>
				</div>
			</form>
			<script nonce="<?=\CSP::nonceRandom();?>">
				$(document).ready(function(){
					validateEqualInputs('newPassword', 'confirmNewPassword');
					$('.sendTelegramButton').on('click', function(event) {
						const telegramId = $('#telegram_id').val();
						if (telegramId.length > 0) {
							$.ajax({
								type: "POST",
								url : "?t="+Math.round((new Date()).getTime() / 1000),
								cache: false,
								dataType: "json",
								data : {
									telegram_id : telegramId,
									sendTelegram : true,
									<?=json_encode(\Csrf::FIELD);?>: <?=json_encode(\Csrf::token());?>
								},
								success : function(d) {
									if (!d) {
										showErrorToast('<?=\T::Framework_Telegram_ErrorSendingMessage();?>');
									} else if (d.error) {
										showErrorToast(d.error);
									} else {
										showSuccessToast('<?=\T::Framework_Telegram_SuccessfullySentMessage();?>');
									}
								},
								error : function(jqXHR, textStatus, errorThrown) {
									showErrorToast('<?=\T::Framework_Telegram_ErrorSendingMessage();?>');
								}
							});
						}
					});
				});
			</script>
		</div>
		<?php
	}

	private function userPasswordCard() {
		?>
		<div class="card card-paddings bg-transparent col-xl-8 p-4 mt-2 mb-3">
			<div class="card-header bg-transparent <?=$this->need_change_password ? 'text-warning' : '';?>"><h3><?=\T::Framework_Settings_ChangePassword();?></h3></div>
			<form class="change-password-form card-body needs-validation" method="post" novalidate>
				<?= \Csrf::field(); ?>
				<?php if ($this->passwordSuccessText) { ?>
					<div class="alert alert-success d-flex align-items-center fade show alert-dismissible" role="alert">
						<div><?=htmlspecialchars($this->passwordSuccessText, ENT_QUOTES, 'UTF-8');?></div>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				<?php } ?>
				<?php if ($this->passwordErrorText) { ?>
					<div class="alert alert-danger d-flex align-items-center fade show alert-dismissible" role="alert">
						<div><?=htmlspecialchars($this->passwordErrorText, ENT_QUOTES, 'UTF-8');?></div>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				<?php } ?>
				<?php
				echo $this->template->html_input("oldPassword", $_POST['oldPassword']??'', \T::Framework_Settings_OldPassword(), true, [
					'type' => 'password',
					'password-alert' => false,
				]);
				echo $this->template->html_input("newPassword", $_POST['newPassword']??'', \T::Framework_Settings_NewPassword(), true, [
					'type' => 'password',
					'password-alert' => true,
				]);
				echo $this->template->html_input("confirmNewPassword", $_POST['confirmNewPassword']??'', \T::Framework_Settings_ConfirmNewPassword(), true, [
					'type' => 'password',
					'invalid-feedback' => \T::Framework_Common_FormPasswordEquals(),
				]);
				?>
				<div class="d-flex flex-row-reverse">
					<button type="submit" class="btn btn-primary" name="changePassword" value="true"><?=\T::Framework_Settings_Change();?></button>
				</div>
			</form>
			<script nonce="<?=\CSP::nonceRandom();?>">
				$(document).ready(function(){
					validateEqualInputs('newPassword', 'confirmNewPassword');

					$('form.change-password-form').on('submit', function(event) {
						if (!validateEqual('newPassword', 'confirmNewPassword')) {
							event.preventDefault()
							event.stopPropagation()
						}
						$('form.change-password-form').addClass('was-validated');
					});
				});
			</script>
		</div>
		<?php
	}

	private function userTwoFactorCard() {

		$currentUser = \Sessions::currentUser();
		if (!$currentUser['two_factor_secret']) {
			$googleAuthenticate = new \GoogleAuthenticator();
			$secret = $googleAuthenticate->generateSecret();
			$url = $googleAuthenticate->render_qrcode($this->currentUser['email'], $secret, \Template::getProjectName());
		}

		?>
		<div class="card card-paddings bg-transparent col-xl-8 p-4 mt-2 mb-3">
			<div class="card-body bg-transparent">
				<div class="d-flex justify-content-between flex-wrap">
					<h3><?=\T::Framework_Settings_TwoFactor_Title();?></h3>
					<?php if (!$currentUser['two_factor_secret']) { ?>
						<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#twoFactorModal">
							<?=\T::Framework_Settings_TwoFactor_Enable();?>
						</button>
					<?php } else { ?>
						<button type="button" class="btn btn-primary btn-danger" data-bs-toggle="modal" data-bs-target="#disableTwoFactorModal">
							<?=\T::Framework_Settings_TwoFactor_Disable();?>
						</button>
					<?php } ?>
				</div>
			</div>
		</div>
		<?php if ($currentUser['two_factor_secret']) { ?>
			<div class="modal" id="disableTwoFactorModal" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<form method="post" class="disable-two-factor-form">
							<?= \Csrf::field(); ?>
							<div class="modal-header">
								<h1 class="modal-title fs-5"><?=\T::Framework_Settings_TwoFactor_Disable();?></h1>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body">
								<?php
								echo $this->template->html_input("disable2faPassword", '', \T::Framework_Settings_OldPassword(), true, [
									'type' => 'password',
									'id' => 'disable2faPassword',
								]);
								?>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?=\T::Framework_Common_Close();?></button>
								<button type="submit" class="btn btn-danger"><?=\T::Framework_Settings_TwoFactor_Disable();?></button>
							</div>
						</form>
					</div>
				</div>
			</div>
			<script nonce="<?=\CSP::nonceRandom();?>">
				$(document).ready(function(){
					$('form.disable-two-factor-form').on('submit', function(event) {
						event.preventDefault();
						$.ajax({
							type: "POST",
							url : "?t="+Math.round((new Date()).getTime() / 1000),
							cache: false,
							dataType: "json",
							data : {
								password: $('#disable2faPassword').val(),
								disableTwoFactor : "true",
								<?=json_encode(\Csrf::FIELD);?>: <?=json_encode(\Csrf::token());?>
							},
							success : function(d) {
								if (!d) {
									showErrorToast('<?=\T::Framework_Settings_TwoFactor_ErrorSetup();?>');
								} else if (d.error) {
									showErrorToast(d.error);
								} else {
									window.location.href="/settings/";
								}
							},
							error : function() {
								showErrorToast('<?=\T::Framework_Settings_TwoFactor_ErrorSetup();?>');
							}
						});
					});
				});
			</script>
		<?php } ?>
		<?php if (!$currentUser['two_factor_secret']) { ?>
			<div class="modal p-0" id="twoFactorModal" tabindex="-1" role="dialog" aria-labelledby="twoFactorModalLabel" aria-modal="true">
				<div class="modal-dialog modal-fullscreen-md-down p-0" role="document">
					<div class="modal-content">
						<form method="post" action="" class="enable-two-factor-form needs-validation m-0" novalidate>
							<?= \Csrf::field(); ?>
							<div class="modal-header">
								<h1 class="modal-title fs-5"><?=\T::Framework_Settings_TwoFactor_Title();?></h1>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body">
								<input type="hidden" name="secret" id="secret" value="<?=htmlspecialchars($secret, ENT_QUOTES, 'UTF-8');?>">
								<div class="d-flex justify-content-left align-items-left mb-3">
									<?=\T::Framework_Settings_TwoFactor_Header();?>
								</div>
								<div class="d-flex justify-content-left align-items-left mb-3">
									<?=\T::Framework_Settings_TwoFactor_Description();?>
								</div>
								<div class="d-flex justify-content-center align-items-center mb-3">
									<img src="<?=$url;?>" alt="" title="" width="250" height="250" />
								</div>
								<div class="d-flex justify-content-center align-items-center">
									Hash Secret
								</div>
								<div class="d-flex justify-content-center align-items-center mb-4">
									<?=$secret;?>
								</div>
								<div class="d-flex justify-content-center align-items-center mb-4">
									<?=\T::Framework_Settings_TwoFactor_EnterCode();?>
								</div>
								<?=$this->template->html_totp('totp');?>
								<p class="mt-3">
									<!--Описание, того что нужно отсканировать QR-code программой GoogleAuthenticator затем вбить 6 значный код в поле ввода.-->
								</p>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?=\T::Framework_Common_Close();?></button>
								<button type="submit" class="btn btn-primary enableTwoFactorButton" name="enableTwoFactor" id="enableTwoFactor" value="true"><?=\T::Framework_Settings_TwoFactor_Enable();?></button>
							</div>
						</form>
					</div>
				</div>
			</div>
			<script nonce="<?=\CSP::nonceRandom();?>">
				$(document).ready(function(){
					$('form.enable-two-factor-form').on('submit', function(event) {
						event.preventDefault()
						event.stopPropagation()

						let totp = $('#totp').val();
						let secret = $('#secret').val();
						if (totp.length == 6) {
							$('form.enable-two-factor-form').addClass('was-validated');
							$.ajax({
								type: "POST",
								url : "?t="+Math.round((new Date()).getTime() / 1000),
								cache: false,
								dataType: "json",
								data : {
									secret: secret,
									totp: totp,
									enableTwoFactor : "true",
									<?=json_encode(\Csrf::FIELD);?>: <?=json_encode(\Csrf::token());?>
								},
								success : function(d) {
									if (!d) {
										showErrorToast('<?=\T::Framework_Settings_TwoFactor_ErrorSetup();?>');
									} else if (d.error) {
										showErrorToast(d.error);
									} else {
										window.location.href="/settings/";
									}

								},
								error : function(jqXHR, textStatus, errorThrown) {
									showErrorToast('<?=\T::Framework_Settings_TwoFactor_ErrorSetup();?>');
								}
							});
						}
					});
				});
			</script>
		<?php } ?>


		<?php
	}
}
