<?php
namespace FrameworkApp\Admin\Users;

class Edit extends \Routing_Parent implements \Routing_Interface {
	private int $user_id = 0;
	private array $user = [];
	private bool $can_read_global = false;
	private bool $can_read = false;
	private bool $can_create_user = false;

	public function Run($user_id=0) {
		$this->user_id = intval($user_id ?? 0);
		$this->check_auth();
		$this->check_permissions();

		$this->processRequest();
		$this->get_data();
		$this->template = new \Template();
		$this->print_header();

		$this->print_forms();
		$this->print_javascript();
	}

	protected function handlePostData(array $data) {
		// Обработка POST данных
		if (!isset($data)) return;
		$this->save_user($data);
	}

	private function save_user($data) {
		if (!isset($data)) return;
		if (!$data) return;

		$params = [];
		do {
			if (isset($data['id']) && $data['id'] && $data['id'] != '') {
				$data['id'] = trim($data['id']);
				if ($this->user_id != intval($data['id'])) {
					$this->error = \T::Framework_Settings_UserNotFound();
					break;
				}
				$params['id'] = intval($data['id']);
			}
			if (isset($data['name']) && $data['name'] && $data['name'] != '') {
				$data['name'] = trim($data['name']);
				if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 64) {
					$this->error = \T::Framework_Settings_NameLengthError();
					break;
				}
				$params['name'] = $data['name'];
			}
			if (isset($data['surname']) && $data['surname'] && $data['surname'] != '') {
				$data['surname'] = trim($data['surname']);
				if (mb_strlen($data['surname']) < 2 || mb_strlen($data['surname']) > 64) {
					$this->error = \T::Framework_Settings_SurnameLengthError();
					break;
				}
				$params['surname'] = $data['surname'];
			}
			if (isset($data['email']) && $data['email'] && $data['email'] != '') {
				$data['email'] = trim($data['email']);
				if (mb_strlen($data['email']) < 6 || mb_strlen($data['email']) > 128) {
					$this->error = \T::Framework_Settings_EmailLengthError();
					break;
				}
				$check_email = \Users::get(['email' => $data['email']]);
				if ($check_email) {
					if ($check_email['id'] != ($params['id']??0)) {
						$this->error = \T::Framework_Settings_EmailAlreadyInUse();
						break;
					}
				}
				$params['email'] = $data['email'];
			}
			if (isset($data['telegram_id']) && $data['telegram_id'] && $data['telegram_id'] != '') {
				$data['telegram_id'] = trim($data['telegram_id']);
				$params['telegram_id'] = strval($data['telegram_id']);
			}
			if (\Config::getInstance()->app_signin_active) {
				if ((isset($data['newPassword']) && $data['newPassword']) || (isset($data['confirmNewPassword']) && $data['confirmNewPassword'])) {
					$data['newPassword'] = trim($data['newPassword']??'');
					$data['confirmNewPassword'] = trim($data['confirmNewPassword']??'');
					if ($data['newPassword'] != $data['confirmNewPassword']) {
						$this->error = \T::Framework_Common_FormPasswordEquals();
						break;
					}
					if ($data['newPassword'] == '') {
						$this->error = \T::Framework_Settings_PasswordRequired();
						break;
					}
					$params['password'] = \Users::password_hash($data['newPassword']);
				}
			}

			if (isset($data['flags']) && $data['flags']) {
				$params['flags'] = common_assemble_flags($data['flags']);
			}
			$old_user = [];
			if (isset($params['id']) && $params['id']) {
				if (!\Sessions::checkPermission(\Users::PERMISSION_MANAGE_USERS, $params['id'], WRITE)) {
					$this->error = \T::Framework_Errors_PermissionDenied();
					break;
				}
				$old_user = \Users::get(['id' => $params['id']]);
				if (!$old_user) {
					$this->error = \T::Framework_Settings_UserNotFound();
					break;
				}
				foreach($params as $k=>$v) {
					if ($old_user[$k] == $v) {
						unset($params[$k]);
					}
				}
				if (!$params) {
					$this->success = \T::Framework_Settings_NotingChanged();
					break;
				}
				$params['id'] = $old_user['id'];
			} else {
				if (!\Sessions::checkPermission(\Users::PERMISSION_CREATE_USER, 0, WRITE)) {
					$this->error = \T::Framework_Errors_PermissionDenied();
					break;
				}
				$params['register_time'] = time();
			}
			$params['update_time'] = time();

			if (isset($params['id']) && $params['id']) {
				// Update User
				$params['_mode'] = \DB\Common::CSMODE_UPDATE;
				$user_id = \Users::save($params);
				$new_user = \Users::get(['id' => $params['id']]);
				\Logs::update_log(\Users::LOGS_OBJECT, $params['id'], $old_user, $new_user,[
					'_save_fields' => ['id'],
				]);
				$user = \Users::get(['id' => $user_id]);
				if (!$user) {
					$this->error = \T::Framework_Settings_UserNotFound();
					break;
				}

				$UserGroups = \UserGroups::get(['user_id' => $user_id, 'group_id' => \Groups::DEFAULT_GROUP_ID]);
				if (!$UserGroups) {
					$UserGroupsId = \UserGroups::save([
						'user_id' => $user_id,
						'group_id' => \Groups::DEFAULT_GROUP_ID,
						'create_time' => time(),
						'update_time' => time(),
						'_mode' => \DB\Common::CSMODE_REPLACE,
					]);
					$UserGroups = \UserGroups::get(['id' => $UserGroupsId]);
					$log_id = \Logs::create_log(\UserGroups::LOGS_OBJECT, $UserGroupsId, $UserGroups);
					\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, \Groups::DEFAULT_GROUP_ID);
					\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $user_id);
				}
			} else {
				// Create User
				$params['creator_user_id'] = \Sessions::currentUser()['id'];
				$params['_mode'] = \DB\Common::CSMODE_INSERT;
				$user_id = \Users::save($params);
				$user = \Users::get(['id' => $user_id]);
				$log_id = \Logs::create_log(\Users::LOGS_OBJECT, $user_id, $user);
				if (!$user) {
					$this->error = \T::Framework_Settings_UserNotFound();
					break;
				}
				$UserGroupsId = \UserGroups::save([
					'user_id' => $user_id,
					'group_id' => \Groups::DEFAULT_GROUP_ID,
					'create_time' => time(),
					'update_time' => time(),
					'_mode' => \DB\Common::CSMODE_REPLACE,
				]);

				$UserGroups = \UserGroups::get(['id' => $UserGroupsId]);
				$log_id = \Logs::create_log(\UserGroups::LOGS_OBJECT, $UserGroupsId, $UserGroups);
				\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, \Groups::DEFAULT_GROUP_ID);
				\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $user_id);

				if (!\Sessions::in_group(\GROUPS::ADMIN_GROUP_ID, intval(\Sessions::currentUser()['id']))) {
					$permissions = \Users::permissions_hash();
					foreach($permissions as $permission => $_) {
						$ObjectPermissions = [
							'object' => 'user',
							'object_id' => intval(\Sessions::currentUser()['id']),
							'subject' => $permission,
							'subject_id' => $user_id,
							READ => 1,
							WRITE => 1,
							DELETE => 1,
							ACCESS_READ => 1,
							ACCESS_WRITE => 1,
							ACCESS_CHANGE => 1,
							'create_time' => time(),
							'update_time' => time(),
							'_mode' => \DB\Common::CSMODE_INSERT,
						];
						$ObjectPermissions['id'] = \ObjectPermissions::save($ObjectPermissions);
						$log_id = \Logs::create_log(\ObjectPermissions::LOGS_OBJECT, $ObjectPermissions['id'], $ObjectPermissions);
						\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $user_id);
					}
				}
			}
			common_redirect('/admin/users/');
		} while(false);
		foreach($data as $key => $value) {
			$this->user[$key] = $value;
		}
	}

	private function check_permissions() {
		\Sessions::requestPermission(\Permissions::ADMIN, 0, READ);

		if ($this->user_id) {
			$this->user = \Users::get(['id' => $this->user_id]);
			if (!$this->user) {
				common_redirect('/admin/users/');
			}
			$this->can_read = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USERS, $this->user_id, READ);
			if (!$this->can_read) {
				e403();
			}
		} else {
			$this->can_read_global = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USERS, -1, READ);
			$this->can_create_user = \Sessions::checkPermission(\Users::PERMISSION_CREATE_USER, 0, WRITE);
			if (!$this->can_read_global && !$this->can_create_user) {
				e403();
			}
		}
	}

	private function get_data() {
		if (!isset($this->user['id'])) $this->user['id'] = '';

		$this->user['flags'] = common_assemble_flags($this->user['flags'] ?? 0);

		if (!$this->user['id']) {
			$this->user['status'] = 1;
		} else {
			$this->user['register_time'] = (
				isset($this->user['register_time']) && $this->user['register_time'] > 0 ?
				date('Y-m-d H:i:s', $this->user['register_time']) : ''
			);
			$this->user['update_time'] = (
				isset($this->user['update_time']) && $this->user['update_time'] > 0 ?
				date('Y-m-d H:i:s', $this->user['update_time']) : ''
			);
			$this->user['login_time'] = (
				isset($this->user['login_time']) && $this->user['login_time'] > 0 ?
				date('Y-m-d H:i:s', $this->user['login_time']) : ''
			);
		}
	}

	private function print_header() {
		?>
		<div class="float-start"><h2><i class="bi bi-person"></i> <?=\T::Framework_Menu_Users();?></h2></div>
		<div class="clearfix"></div>
		<?php
	}

	private function print_forms() {
		?>
		<div class="row d-flex justify-content-center">
			<div class="card bg-transparent col-xl-10 p-4 mt-2 mb-3">
				<div class="card-header bg-transparent"><h3>
					<?php
					if ($this->user['id']) {
						echo \T::Framework_Menu_EditUser($this->user['name'].' '.$this->user['surname'], $this->user['id']);
					} else {
						echo \T::Framework_Menu_CreateUser();
					}
					?>
				</h3></div>
				<form class="card-body needs-validation" method="post" novalidate>
					<input type="hidden" name="id" value="<?=$this->user['id'];?>"/>
					<?php
					echo $this->template->html_input("name", $this->user['name']??'', \T::Framework_Settings_UserProfile_Name(), true);
					echo $this->template->html_input("surname", $this->user['surname']??'', \T::Framework_Settings_UserProfile_Surname(), true);
					echo $this->template->html_input("email", $this->user['email']??'', \T::Framework_Settings_UserProfile_Email(), true, [
						'type' => 'email',
					]);
					echo $this->template->html_input("telegram_id", $this->user['telegram_id']??'', \T::Framework_Settings_UserProfile_TelegramId(), false, [
						'type' => 'text',
					]);
					if (\Config::getInstance()->app_signin_active) {
						echo $this->template->html_input("newPassword", $_POST['newPassword']??'', \T::Framework_Settings_NewPassword(), $this->user['id'] ? false : true, [
							'type' => 'password',
							'password-alert' => true,
						]);
						echo $this->template->html_input("confirmNewPassword", $_POST['confirmNewPassword']??'', \T::Framework_Settings_ConfirmNewPassword(), $this->user['id'] ? false : true, [
							'type' => 'password',
							'invalid-feedback' => \T::Framework_Common_FormPasswordEquals(),
						]);
					}
					if ($this->user['id']) {
						echo $this->template->html_input("register_time", $this->user['register_time']??'', \T::Framework_Settings_RegisterTime(), false, ['readonly' => true]);
						echo $this->template->html_input("update_time", $this->user['update_time']??'', \T::Framework_Settings_UpdateTime(), false, ['readonly' => true]);
						echo $this->template->html_input("login_time", $this->user['login_time']??'', \T::Framework_Settings_LoginTime(), false, ['readonly' => true]);
					}

					echo $this->template->html_switch("status", intval($this->user['status']??0), \T::Framework_Settings_Active());

					echo $this->template->html_params("flags[]", \Users::flags_hash(), intval($this->user['flags']??0), \T::Framework_Settings_Params());
					?>
					<div class="d-flex flex-row-reverse">
						<button type="submit" class="btn btn-primary" name="<?=$this->user['id'] ? 'editUser' : 'createUser';?>" value="true"><?=($this->user['id'] ? \T::Framework_Settings_UserProfile_Change() : \T::Framework_Settings_UserProfile_Create());?></button>
					</div>
				</form>
			</div>

		</div>
		<?php
	}

	private function print_javascript() {
		?>
		<script nonce="<?=\CSP::nonceRandom();?>">
			$(document).ready(function(){
				validateEmailInput('email');
				<?php if (\Config::getInstance()->app_signin_active) { ?>
					validateEqualInputs('newPassword', 'confirmNewPassword');
				<?php } ?>
				$('form').on('submit', function(event) {
					<?php if (\Config::getInstance()->app_signin_active) { ?>
						if (!validateEqual('newPassword', 'confirmNewPassword')) {
							event.preventDefault()
							event.stopPropagation()
						}
					<?php } ?>

					$('form').addClass("was-validated");
				});
			});
		</script>
		<?php
	}
}