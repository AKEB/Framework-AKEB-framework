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

		$this->template = new \Template();
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
		// Обработка GET данных
		if (!isset($data)) return;
		if (isset($data['disable_2fa'])) {
			$this->disableTwoFactor();
		}
	}

	protected function handlePostData(array $data) {
		// Обработка POST данных
		if (!isset($data)) return;

		if (\Config::getInstance()->app_signin_active && isset($data['changePassword']) && $data['changePassword'] == 'true') {
			$this->changePassword($data['oldPassword']??'', $data['newPassword']??'', $data['confirmNewPassword']??'');
		}
		if (isset($data['enableTwoFactor']) && $data['enableTwoFactor'] == 'true') {
			$this->enableTwoFactor($data['secret'], $data['totp']);
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
		if (!$this->currentUser || !isset($this->currentUser['id']) && !$this->currentUser['id'] < 1) {
			$this->passwordErrorText = \T::Framework_Settings_UserNotFound();
			return;
		}
		if (\Users::check_user_credentials($this->currentUser['email'], $oldPassword) != $this->currentUser['id']) {
			$this->passwordErrorText = \T::Framework_Settings_OldPasswordIsIncorrect();
			return;
		}
		$params = [
			'id' => $this->currentUser['id'],
			'password' => md5($newPassword . \Config::getInstance()->password_salt),
			'flags' => $this->currentUser['flags'] & ~\Users::FLAGS_NEED_CHANGE_PASSWORD,
			'updateTime' => time(),
			'_mode' => \DB\Common::CSMODE_UPDATE,
		];
		$old_user = \Users::get(['id' => $this->currentUser['id']]);
		\Users::save($params);
		$new_user = \Users::get(['id' => $this->currentUser['id']]);
		\Logs::update_log(\Users::LOGS_OBJECT, $this->currentUser['id'], $old_user, $new_user);
		$_POST = [];
		$this->currentUser['flags'] = $this->currentUser['flags'] & ~\Users::FLAGS_NEED_CHANGE_PASSWORD;
		\Sessions::set_current_user($this->currentUser['id']);
		$this->passwordSuccessText = \T::Framework_Settings_PasswordChangedSuccessfully();
	}

	private function disableTwoFactor() {
		if (!\Config::getInstance()->app_signin_active) return;
		$currentUser = \Sessions::currentUser();
		if (!isset($currentUser['password']) || !$currentUser['password']) return;
		$user = \Users::get(['id' => $currentUser['id']]);
		if (!$user || !$user['id']) {
			return;
		}
		$user_id = \Users::save([
			'id' => $user['id'],
			'2fa' => '',
			'updateTime' => time(),
			'_mode' => \DB\Common::CSMODE_UPDATE,
		]);
		if ($user_id) {
			common_redirect('/settings/');
		}
		return;
	}

	private function enableTwoFactor($secret, $totp) {
		if (!\Config::getInstance()->app_signin_active) exit;
		$currentUser = \Sessions::currentUser();
		if (!isset($currentUser['password']) || !$currentUser['password']) exit;
		$googleAuthenticate = new \GoogleAuthenticator();
		$check = $googleAuthenticate->checkCode($secret, $totp);
		if (!$check) {
			echo json_encode(['error' => \T::Framework_Login_InvalidTOTP()]);
		}
		$user = \Users::get(['id' => $currentUser['id']]);
		if (!$user || !$user['id']) {
			echo json_encode(['error' => \T::Framework_Settings_UserNotFound()]);
		}
		$user_id = \Users::save([
			'id' => $user['id'],
			'2fa' => $secret,
			'updateTime' => time(),
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
		$telegramId = intval(trim($data['telegram_id']??''));
		if (!$name || !$surname || !$email) {
			$this->errorText = \T::Framework_Settings_AllFieldsAreRequired();
			return;
		}
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->errorText = \T::Framework_Settings_InvalidEmailFormat();
			return;
		}
		if (!$this->currentUser || !isset($this->currentUser['id']) && !$this->currentUser['id'] < 1) {
			$this->passwordErrorText = \T::Framework_Settings_UserNotFound();
			return;
		}
		// Check if email is already used by another user
		$existingUser = \Users::get(['email' => $email], sql_pholder(' AND id <> ?', $this->currentUser['id']));
		if ($existingUser) {
			$this->errorText = \T::Framework_Settings_EmailAlreadyInUse();
			return;
		}

		$params = [
			'id' => $this->currentUser['id'],
			'name' => $name,
			'surname' => $surname,
			'email' => $email,
			'telegram_id' => $telegramId,
			'updateTime' => time(),
			'_mode' => \DB\Common::CSMODE_UPDATE,
		];
		$old_user = \Users::get(['id' => $this->currentUser['id']]);
		\Users::save($params);
		$new_user = \Users::get(['id' => $this->currentUser['id']]);
		\Logs::update_log(\Users::LOGS_OBJECT, $this->currentUser['id'], $old_user, $new_user);
		$this->successText = \T::Framework_Settings_ProfileUpdatedSuccessfully();
		$this->currentUser = \Users::get(['id' => $this->currentUser['id']]);
	}

	private function print_header() {
		echo '<h1>'.\T::Framework_Settings_Title().'</h1>';
		echo \T::Framework_Settings_Subtitle();
	}

	private function userProfileCard() {
		?>
		<div class="card bg-transparent col-xl-8 p-4 mt-2 mb-3">
			<div class="card-header bg-transparent"><h3><?=\T::Framework_Settings_UserProfile_Title();?></h3></div>
			<form class="card-body" method="post">
				<?php if ($this->successText) { ?>
					<div class="alert alert-success d-flex align-items-center fade show alert-dismissible" role="alert">
						<div><?=$this->successText;?></div>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				<?php } ?>
				<?php if ($this->errorText) { ?>
					<div class="alert alert-danger d-flex align-items-center fade show alert-dismissible" role="alert">
						<div><?=$this->errorText;?></div>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				<?php } ?>
				<?php
				echo $this->template->html_input("name", $this->currentUser['name']??'', \T::Framework_Settings_UserProfile_Name(), true);
				echo $this->template->html_input("surname", $this->currentUser['surname']??'', \T::Framework_Settings_UserProfile_Surname(), true);
				echo $this->template->html_input("email", $this->currentUser['email']??'', \T::Framework_Settings_UserProfile_Email(), true, [
					'type' => 'email',
				]);
				echo $this->template->html_input("telegram_id", $this->currentUser['telegram_id']??'', \T::Framework_Settings_UserProfile_TelegramId(), false, [
					'type' => 'number',
				]);
				?>
				<div class="d-flex flex-row-reverse">
					<button type="submit" class="btn btn-primary" name="changeUserProfile" value="true"><?=\T::Framework_Settings_UserProfile_Change();?></button>
				</div>
			</form>
		</div>
		<?php
	}

	private function userPasswordCard() {
		?>
		<div class="card bg-transparent col-xl-8 p-4 mt-2 mb-3">
			<div class="card-header bg-transparent <?=$this->need_change_password ? 'text-warning' : '';?>"><h3><?=\T::Framework_Settings_ChangePassword();?></h3></div>
			<form class="change-password-form card-body needs-validation" method="post" novalidate>
				<?php if ($this->passwordSuccessText) { ?>
					<div class="alert alert-success d-flex align-items-center fade show alert-dismissible" role="alert">
						<div><?=$this->passwordSuccessText;?></div>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				<?php } ?>
				<?php if ($this->passwordErrorText) { ?>
					<div class="alert alert-danger d-flex align-items-center fade show alert-dismissible" role="alert">
						<div><?=$this->passwordErrorText;?></div>
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
		if (!$currentUser['2fa']) {
			$googleAuthenticate = new \GoogleAuthenticator();
			$secret = $googleAuthenticate->generateSecret();
			$url = $googleAuthenticate->render_qrcode($this->currentUser['email'], $secret, \Template::getProjectName());
		}

		?>
		<div class="card bg-transparent col-xl-8 p-4 mt-2 mb-3">
			<div class="card-body bg-transparent">
				<div class="d-flex justify-content-between flex-wrap">
					<h3><?=\T::Framework_Settings_TwoFactor_Title();?></h3>
					<?php if (!$currentUser['2fa']) { ?>
						<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#twoFactorModal">
							<?=\T::Framework_Settings_TwoFactor_Enable();?>
						</button>
					<?php } else { ?>
						<a href="/settings/?disable_2fa" type="button" class="btn btn-primary btn-danger">
							<?=\T::Framework_Settings_TwoFactor_Disable();?>
						</a>
					<?php } ?>
				</div>
			</div>
		</div>
		<?php if (!$currentUser['2fa']) { ?>
			<div class="modal p-0" id="twoFactorModal" tabindex="-1" role="dialog" aria-labelledby="twoFactorModalLabel" aria-modal="true">
				<div class="modal-dialog modal-fullscreen-md-down p-0" role="document">
					<div class="modal-content bg-dark">
						<form method="post" action="" class="enable-two-factor-form needs-validation m-0" novalidate>
							<div class="modal-header">
								<h1 class="modal-title fs-5" id="staticBackdropLiveLabel"><?=\T::Framework_Settings_TwoFactor_Title();?></h1>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body">
								<input type="hidden" name="secret" id="secret" value="<?=$secret;?>">
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
									enableTwoFactor : "true"
								},
								success : function(d) {
									if (!d) {
										new bootstrap.Toast(showErrorToast('<?=\T::Framework_Settings_TwoFactor_ErrorSetup();?>')).show();
									} else if (d.error) {
										new bootstrap.Toast(showErrorToast(d.error)).show();
									} else {
										window.location.href="/settings/";
									}

								},
								error : function(jqXHR, textStatus, errorThrown) {
									new bootstrap.Toast(showErrorToast('<?=\T::Framework_Settings_TwoFactor_ErrorSetup();?>')).show();
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
