<?php

namespace FrameworkApp\Admin;

class Users extends \Routing_Parent implements \Routing_Interface {
	private bool $can_read_global = false;
	private bool $can_read = false;
	private bool $can_delete = false;
	private bool $can_create_user = false;

	private bool $can_impersonate = false;
	private array $users;

	public function __construct() {

	}

	public function Run($action='list', $user_id=null) {
		$this->check_auth();
		$this->check_permissions();

		if ($action == 'delete' && $user_id) {
			$this->processDeleteAction($user_id);
		}

		$this->get_data();

		$this->template = new \Template('Users');
		$this->print_header();
		$this->print_table();
		$this->print_modal();
		$this->print_javascript();
	}

	private function check_permissions() {
		\Sessions::requestPermission(\Permissions::ADMIN, 0, READ);
		$this->can_read_global = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USERS, 0, READ);

		$this->can_read = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USERS, -1, READ);
		$this->can_delete = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USERS, -1, DELETE);

		$this->can_create_user = \Sessions::checkPermission(\Users::PERMISSION_CREATE_USER, 0, WRITE);

		$this->can_impersonate = \Sessions::checkPermission(\Users::PERMISSION_IMPERSONATE_USER, -1, READ);

		if (!$this->can_read_global && !$this->can_read && !$this->can_create_user) {
			e403();
		}
	}

	private function print_header() {
		?>
		<div class="float-start"><h2><i class="bi bi-person"></i> <?=\T::Framework_Menu_Users();?></h2></div>
		<?php if ($this->can_create_user) {
			?>
			<div class="float-end">
				<h3 class="pointer text-info">
					<i class="bi bi-plus-circle createUserAction"> <?=\T::Framework_Common_Create();?></i>
				</h3>
			</div>
			<?php
		}
		?>
		<div class="clearfix"></div>
		<?php
	}

	private function processDeleteAction(int $userId) {
		if (!isset($userId)) return;
		if (!$this->can_delete) {
			$this->error = \T::Framework_Errors_PermissionDenied();
			return;
		}
		if (!$userId) {
			$this->error = \T::Framework_Users_Delete_UserNotFound();
			return;
		}
		if ($userId == \Sessions::currentUser()['id']) {
			$this->error = \T::Framework_Users_Delete_SelfDenied();
			return;
		}
		if (!\Sessions::checkPermission(\Users::PERMISSION_MANAGE_USERS, $userId, DELETE)) {
			$this->error = \T::Framework_Errors_PermissionDenied();
			return;
		}
		$user = \Users::get(['id' => $userId]);
		if (!$user) {
			$this->error = \T::Framework_Users_Delete_UserNotFound();
			return;
		}
		if (\Sessions::in_group(\Groups::ADMIN_GROUP_ID, $userId)) {
			$users_count = \UserGroups::count(['group_id' => \Groups::ADMIN_GROUP_ID]);
			if ($users_count <= 1) {
				$this->error = \T::Framework_Users_Delete_LastAdminDenied();
				return;
			}
		}
		$UserGroups = \UserGroups::data(['user_id' => $userId]);
		if ($UserGroups) {
			foreach($UserGroups as $UserGroup) {
				\UserGroups::delete(['id' => $UserGroup['id']]);
				$log_id = \Logs::delete_log(\UserGroups::LOGS_OBJECT, $UserGroup['id'], $UserGroup);
				\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $UserGroup['user_id']);
				\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, $UserGroup['group_id']);
			}
			\Users::clear_session_cache($userId);
		}
		$sql = [];
		$sql[] = sql_pholder(" (`object` = 'user' AND `object_id` = ?) ", $userId);
		foreach(\Users::permissions_hash() as $permission=>$_) {
			$sql[] = sql_pholder(" (`subject` = ? AND `subject_id` = ?) ", $permission, $userId);
		}
		$sql = ' AND ('.implode(' OR ', $sql).')';
		$ObjectPermissions = \ObjectPermissions::data(false, $sql);
		if ($ObjectPermissions) {
			foreach($ObjectPermissions as $ObjectPermission) {
				\ObjectPermissions::delete(['id' => $ObjectPermission['id']]);
				$log_id = \Logs::delete_log(\ObjectPermissions::LOGS_OBJECT, $ObjectPermission['id'], $ObjectPermission);
				\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $userId);
				if ($ObjectPermission['object'] == 'user' && $ObjectPermission['object_id'] != $userId) {
					\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $ObjectPermission['object_id']);
				} elseif ($ObjectPermission['object'] == 'group') {
					\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, $ObjectPermission['object_id']);
				}
			}
		}
		\Users::delete(['id' => $userId]);
		\Logs::delete_log(\Users::LOGS_OBJECT, $userId, $user);
		common_redirect('/admin/users/');
	}

	private function get_data() {
		$manageUsers = [];
		$sql = '';
		if (!$this->can_read_global) {
			$manageUsers = \Sessions::getAllSubjectPermissions(\Users::PERMISSION_MANAGE_USERS);

			if ($manageUsers) {
				$sql .= sql_pholder(' AND id IN (?@)', array_keys($manageUsers));
			} else {
				$sql .= sql_pholder(' AND 0');
			}
		}
		$this->users = \Users::data(false, $sql, 'id, name, surname, email, status, register_time, login_time');
	}

	private function print_table() {
		?>
		<div class="row d-flex justify-content-center">
			<table class="table table-transparent table-responsive" id="UsersTable">
				<thead class="">
					<tr>
					<th scope="col" class="align-middle" data-priority="1">ID</th>
					<th scope="col" class="align-middle" data-priority="5"><?=\T::Framework_Users_Table_Name();?></th>
					<th scope="col" class="align-middle" data-priority="5"><?=\T::Framework_Users_Table_Surname();?></th>
					<th scope="col" class="align-middle" data-priority="2"><?=\T::Framework_Users_Table_Email();?></th>
					<th scope="col" class="align-middle" data-priority="4"><?=\T::Framework_Users_Table_Status();?></th>
					<th scope="col" class="align-middle text-center" data-priority="6"><?=\T::Framework_Common_RegisterTime();?></th>
					<th scope="col" class="align-middle text-center" data-priority="6"><?=\T::Framework_Common_LoginTime();?></th>
					<th scope="col" class="align-middle text-center" data-priority="3"><?=\T::Framework_Users_Table_Actions();?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($this->users as $user) {
						$params = [
							'id' => intval($user['id'] ?? 0),
							'name' => htmlspecialchars(trim($user['name'] ?? '')),
							'surname' => htmlspecialchars(trim($user['surname'] ?? '')),
							'email' => htmlspecialchars(trim($user['email'] ?? '')),
							'status' => intval($user['status'] ?? 0) == 1 ? '<i class="bi bi-check-lg fs-3 text-success"></i>':'<i class="bi bi-x-lg fs-3 text-danger"></i>',
							'register_time' => isset($user['register_time']) && $user['register_time'] > 0 ? date("Y-m-d H:i:s", $user['register_time']) : '',
							'login_time' => isset($user['login_time']) && $user['login_time'] > 0 ? date("Y-m-d H:i:s", $user['login_time']) : '',
						];

						$can_read_user = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USERS, $params['id'], READ);
						if (!$can_read_user) continue;
						$can_write_user = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USERS, $params['id'], WRITE);
						$can_delete_user = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USERS, $params['id'], DELETE);

						$can_impersonate_user = \Sessions::checkPermission(\Users::PERMISSION_IMPERSONATE_USER, $params['id'], READ);

						if ($params['id'] == \Sessions::currentUser()['id']) {
							$can_delete_user = false;
						}

						$can_read_permissions = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USER_PERMISSIONS, $params['id'], READ);
						$can_write_permissions = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USER_PERMISSIONS, $params['id'], WRITE);
						$can_read_groups = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USER_GROUPS, $params['id'], READ);
						$can_write_groups = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USER_GROUPS, $params['id'], WRITE);

						?>
						<tr>
							<th scope="row" class="align-middle"><?=$params['id'];?></th>
							<td class="align-middle">
								<?php
								if ($can_write_user) {
									?>
									<span class="d-inline pointer text-info editUserAction" data-user-id="<?=$params['id'];?>">
										<?=$params['name'];?>
									</span>
									<?php
								} else {
									?>
									<span class="d-inline">
										<?=$params['name'];?>
									</span>
									<?php
								}
								?>
							</td>
							<td class="align-middle">
								<?php
								if ($can_write_user) {
									?>
									<span class="d-inline pointer text-info editUserAction" data-user-id="<?=$params['id'];?>">
										<?=$params['surname'];?>
									</span>
									<?php
								} else {
									?>
									<span class="d-inline">
										<?=$params['surname'];?>
									</span>
									<?php
								}
								?>
							</td>
							<td class="align-middle">
								<?php
								if ($can_write_user) {
									?>
									<span class="d-inline pointer text-info editUserAction" data-user-id="<?=$params['id'];?>">
										<?=$params['email'];?>
									</span>
									<?php
								} else {
									?>
									<span class="d-inline">
										<?=$params['email'];?>
									</span>
									<?php
								}
								?>
							</td>
							<td class="align-middle text-center"><?=$params['status'];?></td>
							<td class="align-middle text-center"><?=$params['register_time'];?></td>
							<td class="align-middle text-center"><?=$params['login_time'];?></td>
							<td class="align-middle text-center">
								<?php
								if ($can_read_groups || $can_write_groups) {
									?>
									<a href="/admin/users/<?=$params['id'];?>/groups/"><i class="bi bi-people fs-4 text-info pointer" title="<?=\T::Framework_Users_Table_Groups();?>"></i></a>
									<?php
								} else {
									?><i class="bi bi-people fs-4 text-secondary" title="<?=\T::Framework_Users_Table_Groups();?>"></i><?php
								}
								?>
								<?php
								if ($can_read_permissions || $can_write_permissions) {
									?>
									<a href="/admin/permissions/user/<?=$params['id'];?>/"><i class="bi bi-file-earmark-lock fs-4 text-info pointer" title="<?=\T::Framework_Users_Table_Permissions();?>"></i></a>
									<?php
								} else {
									?><i class="bi bi-file-earmark-lock fs-4 text-secondary" title="<?=\T::Framework_Users_Table_Permissions();?>"></i><?php
								}
								?>
								<?php if ($this->can_impersonate) { ?>
									<?php
									if ($can_impersonate_user) {
										?>
										<i class="bi bi-box-arrow-in-right fs-4 text-warning pointer impersonateUserAction"
											data-user-id = "<?=$params['id'];?>"
											title="<?=\T::Framework_Users_Table_ImpersonateUser();?>"
										></i>
										<?php
									} else {
										?><i class="bi bi-box-arrow-in-right fs-4 text-secondary" title="<?=\T::Framework_Users_Table_ImpersonateUser();?>"></i><?php
									}
									?>
								<?php } ?>
								<?php if ($this->can_delete) {?>
									<?php
									if ($can_delete_user) {
										?>
										<i class="bi bi-trash fs-4 text-danger pointer deleteUserAction"
											data-user-id = "<?=$params['id'];?>"
											data-user-name = "<?=addslashes($params['name'].' '.$params['surname']);?>"
											title="<?=\T::Framework_Users_Table_Delete();?>"
										></i>
										<?php
									} else {
										?><i class="bi bi-trash fs-4 text-secondary" title="<?=\T::Framework_Users_Table_Delete();?>"></i><?php
									}
									?>
								<?php } ?>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function print_modal() {
		?>
		<div class="modal" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel">
			<div class="modal-dialog">
				<div class="modal-content">
				<div class="modal-header border-secondary">
					<h5 class="modal-title" id="deleteUserModalLabel"><?=\T::Framework_Users_Delete_Title();?></h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body" id="deleteUserModalBody">
					<?=\T::Framework_Users_Delete_Confirmation();?>
				</div>
				<div class="modal-footer border-secondary">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?=\T::Framework_Common_Cancel();?></button>
					<button type="button" class="btn btn-danger" id="confirmDeleteBtn"><?=\T::Framework_Common_Delete();?></button>
				</div>
				</div>
			</div>
		</div>
		<?php
	}

	private function print_javascript() {
		?>
		<script nonce="<?=\CSP::nonceRandom();?>">
			$(document).ready(function(){
				new DataTable('#UsersTable');

				$('.createUserAction').on('click', function() {
					window.location.href = '/admin/users/edit/';
				});

				$('.editUserAction').on('click', function() {
					window.location.href = '/admin/users/edit/' + $(this).data('user-id') + '/';
				});

				$('.deleteUserAction').on('click', function() {
					showDeleteUserModal($(this).data('user-id'), $(this).data('user-name'));
				});

				$('.impersonateUserAction').on('click', function() {
					window.location.href = '/admin/impersonate/' + $(this).data('user-id') + '/';
				});

			});
			function showDeleteUserModal(userId, userTitle) {
				const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
				const modalBody = document.getElementById('deleteUserModalBody');
				const confirmBtn = document.getElementById('confirmDeleteBtn');

				modalBody.textContent = '<?=\T::Framework_Users_Delete_Confirmation();?>'.replace('{user}', userTitle);

				confirmBtn.onclick = function() {
					window.location.href = '/admin/users/delete/' + userId + '/';
					// window.location.href = '?action=delete&id=' + userId;
				};

				modal.show();

				const modalElement = document.getElementById('deleteUserModal');
				modalElement.addEventListener('hide.bs.modal', event => {
					const focusedElement = document.activeElement;
					if (modalElement.contains(focusedElement)) {
						focusedElement.blur();
					}
				});
			}
		</script>
		<?php
	}

}