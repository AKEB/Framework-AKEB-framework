<?php

namespace FrameworkApp;

class Forgot extends \Routing_Parent implements \Routing_Interface {

	private string $token = '';
	private string $errorText = '';
	private string $successText = '';
	private ?array $user = null;

	public function Run() {
		if (!\Config::getInstance()->app_signin_active) {
			common_redirect('/');
		}
		$this->processRequest();

		$this->template = new \Template(false);
		$this->print_form();
	}

	protected function handleGetData(array $data) {
		if (isset($data['token'])) {
			$this->token = $data['token'];
			$this->user = \Users::get(['reset_token' => $this->token], sql_pholder(' AND reset_token_expires > ?', time()));
			if (!$this->user) {
				$this->errorText = \T::Framework_Forgot_InvalidOrExpiredToken();
				$this->token = ''; // Invalidate token to show the initial form
			}
		}
	}

	protected function handlePostData(array $data) {
		if (isset($data['action'])) {
			if ($data['action'] == 'request_reset' && isset($data['email'])) {
				$this->requestReset($data['email']);
			} elseif ($data['action'] == 'reset_password' && isset($data['token'])) {
				$this->resetPassword($data['token'], $data['newPassword'] ?? '', $data['confirmNewPassword'] ?? '');
			}
		}
	}

	private function requestReset(string $email) {
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->errorText = \T::Framework_Settings_InvalidEmailFormat();
			return;
		}

		$user = \Users::get(['email' => $email]);
		if ($user) {
			// Generate a secure random token
			$token = \Users::generate_reset_token();
			// Set token expiration (e.g., 1 hour from now)
			$expires = time() + 1800;

			$resetLink = strval($_SERVER['HTTP_REFERER'] ?? '').'?token=' . $token;
			$subject = \T::Framework_Forgot_EmailSubject();
			$body = \T::Framework_Forgot_EmailBody($resetLink);

			\Users::save([
				'id' => $user['id'],
				'reset_token' => $token,
				'reset_token_expires' => $expires,
				'update_time' => time(),
				'_mode' => \DB\Common::CSMODE_UPDATE,
			]);
			$new_user = \Users::get(['id' => $user['id']]);
			\Logs::update_log(\Users::LOGS_OBJECT, $this->user['id'], $user, $new_user,[
				'ip' => \Sessions::client_ip(),
			],'',$this->user['id'],$this->user['id']);
			// Assuming you have a mailer service
			\Mail::send($user['email'], $subject, $body);
		}

		// Always show a generic success message to prevent user enumeration
		$this->successText = \T::Framework_Forgot_InstructionsSent();
	}

	private function resetPassword(string $token, string $newPassword, string $confirmNewPassword) {
		$this->token = $token;
		$this->user = \Users::get(['reset_token' => $this->token], sql_pholder(' AND reset_token_expires > ?', time()));

		if (!$this->user) {
			$this->errorText = \T::Framework_Forgot_InvalidOrExpiredToken();
			return;
		}

		if (!$newPassword || !$confirmNewPassword) {
			$this->errorText = \T::Framework_Settings_AllFieldsAreRequired();
			return;
		}

		if ($newPassword !== $confirmNewPassword) {
			$this->errorText = \T::Framework_Settings_NewPasswordsDoNotMatch();
			return;
		}

		$newPasswordHash = \Users::password_hash($newPassword);
		$params = [
			'id' => $this->user['id'],
			'password' => $newPasswordHash,
			'reset_token' => '',
			'reset_token_expires' => 0,
			'flags' => $this->user['flags'] & ~\Users::FLAGS_NEED_CHANGE_PASSWORD,
			'update_time' => time(),
			'_mode' => \DB\Common::CSMODE_UPDATE,
		];
		$old_user = \Users::get(['id' => $this->user['id']]);
		\Users::save($params);
		$new_user = \Users::get(['id' => $this->user['id']]);
		\Logs::update_log(\Users::LOGS_OBJECT, $this->user['id'], $old_user, $new_user,[
			'ip' => \Sessions::client_ip(),
		],'',$this->user['id'],$this->user['id']);
		// Redirect to login page with a success message
		common_redirect('/login/');
	}

	private function print_form() {
		?>
		<div class="container py-5 h-100">
			<div class="row d-flex justify-content-center align-items-center h-100">
				<div class="col-12 col-md-8 col-lg-6 col-xl-5">
					<div class="card bg-dark text-white loginCard">
						<div class="card-body p-5 pt-1 text-center">
							<div class="mb-md-3 mt-md-4 pb-5">
								<img src="/images/apple-icon-120x120.png" alt="Icon" width="120" height="120" class="rounded-circle">
								<?php if ($this->errorText) { ?>
									<div class="alert alert-danger mt-4" role="alert">
										<?= htmlspecialchars($this->errorText) ?>
									</div>
								<?php } ?>
								<?php if ($this->successText) { ?>
									<div class="alert alert-success mt-4" role="alert">
										<?= htmlspecialchars($this->successText) ?>
									</div>
								<?php } ?>

								<?php if ($this->token && $this->user) { ?>
									<!-- Reset Password Form -->
									<p class="text-white-50 mb-3"><?= \T::Framework_Forgot_ResetSubtitle() ?></p>
									<form action="/forgot/" class="change-password-form card-body needs-validation" method="post" novalidate>
										<input type="hidden" name="action" value="reset_password"/>
										<input type="hidden" name="token" value="<?= htmlspecialchars($this->token) ?>"/>
										<?php
										echo $this->template->html_input("newPassword", $_POST['newPassword']??'', \T::Framework_Settings_NewPassword(), true, [
											'type' => 'password',
											'password-alert' => true,
											'class1' => 'col-12',
											'class2' => 'col-12',
										]);
										echo $this->template->html_input("confirmNewPassword", $_POST['confirmNewPassword']??'', \T::Framework_Settings_ConfirmNewPassword(), true, [
											'type' => 'password',
											'invalid-feedback' => \T::Framework_Common_FormPasswordEquals(),
											'class1' => 'col-12',
											'class2' => 'col-12',
										]);
										?>
										<button class="btn btn-outline-light btn-lg px-5 mt-3" type="submit"><?= \T::Framework_Forgot_ResetButton() ?></button>
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
								<?php } elseif (!$this->successText) { ?>
									<!-- Request Reset Form -->
									<p class="text-white-50 mb-3"><?= \T::Framework_Forgot_Subtitle() ?></p>
									<form action="/forgot/" method="post">
										<input type="hidden" name="action" value="request_reset"/>
										<div data-mdb-input-init class="form-outline form-white mb-2">
											<label class="form-label" for="email"><?=\T::Framework_Login_Email();?></label>
											<input type="email" id="email" name="email" class="form-control form-control-lg" value="<?=isset($_POST['email']) && $_POST['email'] ?htmlspecialchars($_POST['email']) : ''?>"/>
										</div>
										<button class="btn btn-outline-light btn-lg px-5 mt-3" type="submit"><?= \T::Framework_Forgot_RequestButton() ?></button>
									</form>
								<?php } ?>
								<div class="mt-4">
									<p class="mb-0"><a href="/login/" class="text-white-50 fw-bold"><?= \T::Framework_Forgot_BackToLogin() ?></a></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
