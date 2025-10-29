<?php

namespace FrameworkApp;

class Signup extends \Routing_Parent implements \Routing_Interface {

	private string $token = '';
	private string $errorText = '';
	private string $successText = '';

	public function Run() {
		if (!\Config::getInstance()->app_signup_active) {
			common_redirect('/');
		}

		$this->processRequest();

		$this->template = new \Template('SignUp', false);
		$this->print_form();
	}

	protected function handleGetData(array $data) {
		if (isset($data['token'])) {
			common_redirect('/login/?token='.$data['token']);
		}
	}

	protected function handlePostData(array $data) {
		if (isset($data['action'])) {
			if ($data['action'] == 'signup') {
				$this->signup($data);
			}
		}
	}
	private function signup(array $data) {
		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$this->errorText = \T::Framework_Settings_InvalidEmailFormat();
			return;
		}
		if ($data['newPassword'] != $data['confirmNewPassword']) {
			$this->errorText = \T::Framework_Settings_NewPasswordsDoNotMatch();
			return;
		}
		$user = \Users::get(['email' => $data['email']]);
		if ($user) {
			$this->errorText = \T::Framework_Settings_EmailAlreadyInUse();
			return;
		}
		$user = [
			'name' => $data['name'],
			'surname' => $data['surname'],
			'email' => $data['email'],
			'email_verification_token' => \Users::generate_verification_token(),
			'telegram_id' => '',
			'password' => \Users::password_hash($data['newPassword']),
			'reset_token' => '',
			'reset_token_expires' => 0,
			'two_factor_secret' => '',
			'status' => \Users::STATUS_ACTIVE,
			'creator_user_id' => 0,
			'register_time' => time(),
			'login_time' => 0,
			'update_time' => time(),
			'login_try_time' => 0,
			'flags' => 0,
			'cookie' => '{}'
		];
		$user['id'] = \Users::save($user);
		if ($user['id']) {
			\Logs::create_log(\Users::LOGS_OBJECT, $user['id'], $user,[
				'ip' => \Sessions::client_ip(),
			],'',$user['id'],$user['id']);
			$UserGroupsId = \UserGroups::save([
				'user_id' => $user['id'],
				'group_id' => \Groups::DEFAULT_GROUP_ID,
				'create_time' => time(),
				'update_time' => time(),
				'_mode' => \DB\Common::CSMODE_REPLACE,
			]);
			$UserGroups = \UserGroups::get(['id' => $UserGroupsId]);
			$log_id = \Logs::create_log(\UserGroups::LOGS_OBJECT, $UserGroupsId, $UserGroups,[
				'ip' => \Sessions::client_ip(),
			],'',$user['id'],$user['id']);
			\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, \Groups::DEFAULT_GROUP_ID);
			\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $user['id']);

			$resetLink = strval($_SERVER['HTTP_REFERER'] ?? '').'?token=' . $user['email_verification_token'];
			$subject = \T::Framework_SignUp_EmailSubject();
			$body = \T::Framework_SignUp_EmailBody($resetLink);

			\Mail::send($user['email'], $subject, $body);
			$this->successText = \T::Framework_SignUp_Success();
		} else {
			$this->errorText = \T::Framework_SignUp_Error();
		}
	}

	private function print_form() {
		?>
		<div class="container py-5 h-100">
			<div class="row d-flex justify-content-center align-items-center h-100">
				<div class="col-12 col-md-8 col-lg-6 col-xl-5">
					<div class="card loginCard">
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

								<?php if (!$this->successText) { ?>
									<form action="/signup/" method="post" class="mt-5 register-form card-body needs-validation" novalidate>
										<input type="hidden" name="action" value="signup"/>
										<?php
										echo $this->template->html_input("name", $_POST['name']??'', \T::Framework_Settings_UserProfile_Name(), true,[
											'class1' => 'col-12',
											'class2' => 'col-12',
										]);
										echo $this->template->html_input("surname", $_POST['surname']??'', \T::Framework_Settings_UserProfile_Surname(), true,[
											'class1' => 'col-12',
											'class2' => 'col-12',
										]);
										echo $this->template->html_input("email", $_POST['email']??'', \T::Framework_Settings_UserProfile_Email(), true, [
											'type' => 'email',
											'class1' => 'col-12',
											'class2' => 'col-12',
										]);
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
										<button class="btn btn-outline-secondary btn-lg px-5 mt-3" type="submit"><?= \T::Framework_Login_SignUp() ?></button>
									</form>
									<script nonce="<?=\CSP::nonceRandom();?>">
										$(document).ready(function(){
											validateEqualInputs('newPassword', 'confirmNewPassword');
											validateEmailInput('email');
											$('form.register-form').on('submit', function(event) {
												if (!validateEmail('email')) {
													event.preventDefault();
													event.stopPropagation();
													$('#email').next('.invalid-feedback').text("<?=\T::Framework_Settings_InvalidEmailFormat();?>");
												}
												if (!validateEqual('newPassword', 'confirmNewPassword')) {
													event.preventDefault()
													event.stopPropagation()
												}
												$('form.register-form').addClass('was-validated');
											});

										});
									</script>
								<?php } ?>
								<div class="mt-4">
									<p class="mb-0"><a href="/login/" class="text-secondary-50 fw-bold"><?= \T::Framework_Forgot_BackToLogin() ?></a></p>
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
