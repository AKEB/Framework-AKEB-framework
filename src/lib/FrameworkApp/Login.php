<?php

namespace FrameworkApp;

class Login extends \Routing_Parent implements \Routing_Interface {

	private string $errorText = '';
	private string $successText = '';

	private bool $need_2fa_form = false;
	private string $email = '';
	private string $password = '';

	private string $token = '';
	private int $email_verification_user = 0;

	public function Run() {
		$this->processRequest();

		$this->template = new \Template(false);
		$this->print_form();
	}

	protected function handleGetData(array $data) {
		// Обработка GET данных
		if (!isset($data)) return;
		if (!is_array($data)) return;

		if (isset($data['token'])) {
			$this->token = $data['token'];
			$user = \Users::get(['email_verification_token' => $this->token]);
			if (!$user) {
				$this->errorText = \T::Framework_SignUp_InvalidOrExpiredToken();
				$this->token = ''; // Invalidate token to show the initial form
			} else {
				$param = [
					'id' => $user['id'],
					'email_verification_token' => '',
					'status' => \Users::STATUS_ACTIVE,
					'update_time' => time(),
					'_mode' => \DB\Common::CSMODE_UPDATE,
				];
				\Users::save($param);
				$new_user = \Users::get(['id' => $user['id']]);
				\Logs::update_log(\Users::LOGS_OBJECT, $user['id'], $user, $new_user,[
					'ip' => \Sessions::client_ip(),
					'_save_fields' => ['id'],
				],'',$user['id'],$user['id']);
				$this->token = '';
				$this->email_verification_user = 0;
				$_POST['email'] = $user['email'];
				$this->successText = \T::Framework_Login_EmailVerified();
				common_redirect('/login/');
			}
		} elseif (isset($data['oidc']) && isset($data['code']) && isset($data['state'])) {
			$this->openIdCode($data);
		} elseif (isset($data['oauth']) && isset($data['code']) && isset($data['state'])) {
			$this->oAuthCode($data);
		}
	}

	protected function handlePostData(array $data) {
		// Обработка POST данных
		if (!isset($data)) return;
		if (!$data) return;
		if (!is_array($data)) return;

		if (isset($data['action']) && $data['action'] == 'signin') {
			if (isset($data['resendEmail'])) {
				if (isset($data['user_id'])) {
					$user = \Users::get(['id' => $data['user_id']]);
					if (!$user) return;
					if ($user['email_verification_token']) {
						$resetLink = strval($_SERVER['HTTP_REFERER'] ?? '').'?token=' . $user['email_verification_token'];
						$subject = \T::Framework_SignUp_EmailSubject();
						$body = \T::Framework_SignUp_EmailBody($resetLink);

						\Mail::send($user['email'], $subject, $body);
						$this->successText = \T::Framework_Login_ResendEmailSuccess();
					}
				}
			} elseif (isset($data['signIn'])) {
				$this->signIn($data['email']??'', $data['password']??'', intval($data['totp']??0));
			} elseif(isset($data['openID'])) {
				$this->openIdRedirect();
			} elseif(isset($data['oAuth'])) {
				$this->oAuthRedirect();
			}
		}
	}

	private function openIdCode($data) {
		if (!isset($data['oidc']) || !isset($data['code']) || !isset($data['state'])) return false;
		$provider_url = \Config::getInstance()->openidconnect_provider;
		$provider_url = str_replace('/.well-known/openid-configuration', '', $provider_url);
		$client_id = \Config::getInstance()->openidconnect_client_id;
		$client_secret = \Config::getInstance()->openidconnect_client_secret;
		if (!$provider_url || !$client_id || !$client_secret) return false;

		$oidc = new \Jumbojett\OpenIDConnectClient($provider_url, $client_id, $client_secret);
		$oidc->setRedirectURL(strval($_SERVER['HTTP_REFERER'] ?? '').'?oidc=true');
		$oidc->addScope(explode(' ', \Config::getInstance()->openidconnect_scope));
		try{
			$oidc->authenticate();
			$data = $oidc->requestUserInfo();
			if (!isset($data) || !$data) return false;
			$data = [
				'sub' => $data->sub,
				'email_verified' => $data->email_verified,
				'name' => $data->name,
				'preferred_username' => $data->preferred_username,
				'given_name' => $data->given_name,
				'email' => $data->email,
				'access_token' => $oidc->getAccessToken(),
				'refresh_token' => $oidc->getRefreshToken(),
				'id_token' => $oidc->getIdToken(),
				'OIDC' => true,
			];
			$name = explode(' ', $data['name']);
			$data['name'] = $name[0]??'';
			$data['surname'] = $name[1]??'';
			if (!$data['email'] || !$data['email_verified']) return false;
			$check_user = \Users::get(['email' => $data['email']]);
			if ($check_user) {
				if ($check_user['status'] == \Users::STATUS_INACTIVE) return false;
				return $this->loginUserWithId($check_user['id']);
			}
			if (!\Config::getInstance()->openidconnect_register) return false;
			$userId = \Users::save([
				'name' => $data['name'],
				'surname' => $data['surname'],
				'email' => $data['email'],
				'telegram_id' => '',
				'password' => '',
				'status' => \Users::STATUS_ACTIVE,
				'creator_user_id' => 0,
				'register_time' => time(),
				'update_time' => time(),
				'login_time' => time(),
				'login_try_time' => 0,
				'flags' => 0,
				'cookie' => '{}',
			]);
			if (!$userId) return false;

			$UserGroupsId = \UserGroups::save([
				'user_id' => $userId,
				'group_id' => \Groups::DEFAULT_GROUP_ID,
				'create_time' => time(),
				'update_time' => time(),
				'_mode' => \DB\Common::CSMODE_REPLACE,
			]);
			$UserGroups = \UserGroups::get(['id' => $UserGroupsId]);
			$log_id = \Logs::create_log(\UserGroups::LOGS_OBJECT, $UserGroupsId, $UserGroups);
			\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, \Groups::DEFAULT_GROUP_ID);
			\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $userId);

			return $this->loginUserWithId($userId);
		} catch (\Throwable) {
			$this->errorText = \T::Framework_Login_AuthenticateError();
			return false;
		}
	}

	private function openIdRedirect() {
		$provider_url = \Config::getInstance()->openidconnect_provider;
		$provider_url = str_replace('/.well-known/openid-configuration', '', $provider_url);
		$client_id = \Config::getInstance()->openidconnect_client_id;
		$client_secret = \Config::getInstance()->openidconnect_client_secret;
		if (!$provider_url || !$client_id || !$client_secret) return false;

		$oidc = new \Jumbojett\OpenIDConnectClient($provider_url, $client_id, $client_secret);
		$oidc->setRedirectURL(strval($_SERVER['HTTP_REFERER'] ?? '').'?oidc=true');
		$oidc->addScope(explode(' ', \Config::getInstance()->openidconnect_scope));
		try{
			$oidc->authenticate();
		} catch (\Throwable) {
			$this->errorText = \T::Framework_Login_AuthenticateError();
			return false;
		}
	}

	private function oAuthCode($data) {
		$client_id = \Config::getInstance()->oauth_client_id;
		$client_secret = \Config::getInstance()->oauth_client_secret;
		$scopes = \Config::getInstance()->oauth_scope;
		if (!$client_id || !$client_secret) return false;

		$oauth = new \OAuth($client_id, $client_secret);
		$oauth->redirect_uri = strval($_SERVER['HTTP_REFERER'] ?? '').'?oauth=true';
		$oauth->scopes = $scopes;
		$oauth->authorization_endpoint = \Config::getInstance()->oauth_authorization_endpoint;
		$oauth->token_endpoint = \Config::getInstance()->oauth_token_endpoint;
		$oauth->userinfo_endpoint = \Config::getInstance()->oauth_userinfo_endpoint;
		try{
			$oauth->authenticate();
			$data = $oauth->requestUserInfo();
			if (!isset($data) || !$data) return false;

			$check_user = \Users::get(['email' => $data['email']]);
			if ($check_user) {
				if ($check_user['status'] == \Users::STATUS_INACTIVE) return false;
				return $this->loginUserWithId($check_user['id']);
			}
			if (!\Config::getInstance()->oauth_register) return false;
			$userId = \Users::save([
				'name' => $data['first_name'],
				'surname' => $data['last_name'],
				'email' => $data['email'],
				'telegram_id' => '',
				'password' => '',
				'status' => \Users::STATUS_ACTIVE,
				'creator_user_id' => 0,
				'register_time' => time(),
				'update_time' => time(),
				'login_time' => time(),
				'login_try_time' => 0,
				'flags' => 0,
				'cookie' => '{"lang": "'.($data['language']??'en').'"}',
			]);
			if (!$userId) return false;

			$UserGroupsId = \UserGroups::save([
				'user_id' => $userId,
				'group_id' => \Groups::DEFAULT_GROUP_ID,
				'create_time' => time(),
				'update_time' => time(),
				'_mode' => \DB\Common::CSMODE_REPLACE,
			]);
			$UserGroups = \UserGroups::get(['id' => $UserGroupsId]);
			$log_id = \Logs::create_log(\UserGroups::LOGS_OBJECT, $UserGroupsId, $UserGroups);
			\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, \Groups::DEFAULT_GROUP_ID);
			\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $userId);

			return $this->loginUserWithId($userId);
		} catch (\Throwable) {
			$this->errorText = \T::Framework_Login_AuthenticateError();
			return false;
		}
	}

	private function oAuthRedirect() {
		$client_id = \Config::getInstance()->oauth_client_id;
		$client_secret = \Config::getInstance()->oauth_client_secret;
		$scopes = \Config::getInstance()->oauth_scope;
		if (!$client_id || !$client_secret) return false;

		$oauth = new \OAuth($client_id, $client_secret);
		$oauth->redirect_uri = strval($_SERVER['HTTP_REFERER'] ?? '').'?oauth=true';
		$oauth->scopes = $scopes;
		$oauth->authorization_endpoint = \Config::getInstance()->oauth_authorization_endpoint;
		$oauth->token_endpoint = \Config::getInstance()->oauth_token_endpoint;
		$oauth->userinfo_endpoint = \Config::getInstance()->oauth_userinfo_endpoint;
		try{
			$oauth->authenticate();
		} catch (\Throwable) {
			$this->errorText = \T::Framework_Login_AuthenticateError();
			return false;
		}
	}

	private function loginUserWithId($userId) {
		if (!$userId) {
			$this->errorText = \T::Framework_Login_InvalidCredentials();
			return;
		}
		if ($userId == -1) {
			$this->errorText = \T::Framework_Login_TooManyLoginAttempts();
			return;
		}
		\Sessions::set_current_user($userId);
		$sessionCheck = \Sessions::session_init(true);
		if (!$sessionCheck) {
			$this->errorText = "Fatal Error!";
			return;
		}
		$currentUser = \Sessions::currentUser();
		if (!isset($currentUser) || !$currentUser || !is_array($currentUser) || !isset($currentUser['id'])) {
			$this->errorText = "Fatal Error!";
			return;
		}
		$params = [
			'id' => $currentUser['id'],
			'login_time' => time(),
			'login_try_time' => 0,
			'update_time' => time(),
			'_mode' => \DB\Common::CSMODE_UPDATE,
		];
		\Users::save($params);
		\Logs::log('Login',\Logs::ACTION_LOGIN,\Users::LOGS_OBJECT, $currentUser['id'],[
			'ip' => \Sessions::client_ip(),
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],

		]);
		if (isset($_COOKIE['target']) && $_COOKIE['target']) {
			$target = $_COOKIE['target'];
			setcookie('target', '', time() + 1, '/');
			$_COOKIE['target'] = '';
			$target = str_replace('%2F', '/', urldecode($target));
			common_redirect($target);
		} else {
			common_redirect('/');
		}
	}

	private function signIn(string $email, string $password, int $totp=0) {
		if (!\Config::getInstance()->app_signin_active) {
			$this->errorText = \T::Framework_Login_SignInWithLoginAndPasswordDenied();
			return;
		}
		$email = strval($email??'');
		$password = strval($password??'');
		if (!$email || !$password) {
			$this->errorText = \T::Framework_Login_EmailAndPasswordRequired();
			return;
		}
		$userId = \Users::check_user_credentials($email, $password);

		$user = \Users::get(['id' => $userId]);
		if (isset($user) && isset($user['email_verification_token']) && $user['email_verification_token']) {
			$this->email_verification_user = $user['id'];
			$this->errorText = \T::Framework_Login_EmailNotVerified();
			return;
		}

		if (isset($user['two_factor_secret']) && $user['two_factor_secret']) {
			if ($totp) {
				$googleAuthenticate = new \GoogleAuthenticator();
				if (!$googleAuthenticate->checkCode($user['two_factor_secret'], $totp)) {
					$this->errorText = \T::Framework_Login_InvalidTOTP();
					return;
				}
			} else {
				$this->need_2fa_form = true;
				$this->email = $email;
				$this->password = $password;
				return true;
			}
		}

		return $this->loginUserWithId($userId);
	}

	private function print_form() {
		?>
		<div class="container py-5 h-100">
			<div class="row d-flex justify-content-center align-items-center h-100">
				<div class="col-12 col-md-8 col-lg-6 col-xl-5">
					<div class="card loginCard">
						<div class="card-body p-5 pt-1 text-center">
							<form action="/login/" method="post">
								<input type="hidden" name="action" value="signin"/>
								<div class="mb-md-3 mt-md-4 pb-5">
									<img src="/images/apple-icon-120x120.png" alt="Login" width="120" height="120" class="rounded-circle">
									<?php if ($this->errorText) { ?>
										<div class="alert alert-danger" role="alert">
											<?=htmlspecialchars($this->errorText)?>
										</div>
									<?php } ?>
									<?php if ($this->successText) { ?>
										<div class="alert alert-success mt-4" role="alert">
											<?= htmlspecialchars($this->successText) ?>
										</div>
									<?php } ?>
									<?php if ($this->need_2fa_form && $this->email && $this->password) { ?>
										<div class="d-flex justify-content-center align-items-center mb-4">
											<?=\T::Framework_Login_TwoFactor_Title();?>
										</div>
										<div class="d-flex justify-content-center align-items-center mb-4">
											<?=\T::Framework_Login_TwoFactor_Description();?>
										</div>
										<input type="hidden" name="email"  value="<?=$this->email;?>">
										<input type="hidden" name="password"  value="<?=$this->password;?>">
										<?php $this->template->html_totp('totp');?>
										<button class="btn btn-outline-secondary btn-lg px-5 mb-3 signInButton" name="signIn" type="submit"><?=\T::Framework_Login_SignIn();?></button><br/>
									<?php } elseif ($this->email_verification_user) { ?>
										<input type="hidden" name="user_id"  value="<?=$this->email_verification_user;?>">
										<button data-mdb-button-init data-mdb-ripple-init class="btn btn-outline-secondary btn-lg px-5 mb-3" name="resendEmail" value="true" type="submit"><?=\T::Framework_Login_ResendEmailVerificationButton();?></button><br/>
									<?php } else { ?>
										<?php if (\Config::getInstance()->app_signin_active) { ?>
											<p class="text-secondary-50 mb-3"><?=\T::Framework_Login_Subtitle();?></p>
											<div data-mdb-input-init class="form-outline form-secondary mb-2">
												<label class="form-label" for="typeEmailX"><?=\T::Framework_Login_Email();?></label>
												<input type="email" id="typeEmailX" name="email" class="form-control form-control-lg" value="<?=isset($_POST['email']) && $_POST['email'] ?htmlspecialchars($_POST['email']) : ''?>"/>
											</div>
											<div data-mdb-input-init class="form-outline form-secondary mb-2">
												<label class="form-label" for="typePasswordX"><?=\T::Framework_Login_Password();?></label>
												<div class="input-group">
													<input type="password" class="form-control form-control-lg" id="typePasswordX" name="password">
													<button class="btn btn-secondary btn-lg togglePassword" data-input-id="typePasswordX" type="button" tabindex="-1">
														<i class="bi bi-eye" id="typePasswordX-icon"></i>
													</button>
												</div>
											</div>
											<p class="small mb-3 pb-lg-2"><a class="text-secondary-50" href="/forgot/"><?=\T::Framework_Login_ForgotPassword();?></a></p>
											<button data-mdb-button-init data-mdb-ripple-init class="btn btn-outline-secondary btn-lg px-5 mb-3" name="signIn" type="submit"><?=\T::Framework_Login_SignIn();?></button><br/>
										<?php } ?>
										<?php
										if (\Config::getInstance()->openidconnect_provider && \Config::getInstance()->openidconnect_client_id) {
											?>
											<button data-mdb-button-init data-mdb-ripple-init class="btn btn-outline-secondary btn-lg px-5 mt-5 mb-3" name="openID" type="submit"><?=\T::Framework_Login_LoginWith(\Config::getInstance()->openidconnect_button??\T::Framework_Login_OpenID());?></button>
											<?php
										}
										?>
										<?php
										if (\Config::getInstance()->oauth_client_id && \Config::getInstance()->oauth_client_secret) {
											?>
											<button data-mdb-button-init data-mdb-ripple-init class="btn btn-outline-secondary btn-lg px-5 mt-5 mb-3" name="oAuth" type="submit"><?=\T::Framework_Login_LoginWith(\Config::getInstance()->oauth_button??\T::Framework_Login_OAuth());?></button>
											<?php
										}
										?>
										<?php
										if (\Config::getInstance()->app_signin_active && \Config::getInstance()->app_signup_active) {
											?>
											<div class="mt-4">
												<p class="mb-0"><?=\T::Framework_Login_NoAccount();?> <a href="/signup/" class="text-secondary-50 fw-bold"><?=\T::Framework_Login_SignUp();?></a></p>
											</div>
											<?php
										}
										?>
										<?php
									}
									?>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
