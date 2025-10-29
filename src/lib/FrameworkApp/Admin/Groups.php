<?php
namespace FrameworkApp\Admin;

class Groups extends \Routing_Parent implements \Routing_Interface {

	private bool $can_read_global = false;
	private bool $can_read = false;
	private bool $can_write = false;
	private bool $can_delete = false;
	private bool $can_create_group = false;

	private array $groups = [];
	private array $groups_counts_hash = [];


	public function Run($action='list', $group_id=null) {
		$this->check_auth();
		$this->check_permissions();

		if ($action == 'save') {
			$this->save($_POST);
		} else if ($action == 'delete') {
			$this->delete($group_id);
		}

		$this->get_data();

		$this->template = new \Template('Groups');
		$this->print_header();
		$this->print_table();
		$this->print_modal();
		$this->print_javascript();
	}

	private function save($params) {
		if (!isset($params)) return false;
		if (!$params) return false;
		$groupId = intval($params['id'] ?? 0);
		$groupTitle = trim($params['title'] ?? '');
		if (!$groupTitle) {
			$this->error = \T::Framework_Groups_Create_TitleRequired();
			return;
		}
		if ($groupId) {
			// Update
			if (!\Sessions::checkPermission(\Groups::PERMISSION_MANAGE_GROUPS, $groupId, WRITE)) {
				$this->error = \T::Framework_Errors_PermissionDenied();
				return;
			}
			$oldGroup = \Groups::get(['id' => $groupId]);
			if (!$oldGroup) {
				$this->error = \T::Framework_Groups_Update_GroupNotFound();
				return;
			}

			$params = [
				'id' => $groupId,
				'title' => $groupTitle,
				'update_time' => time(),
				'_mode' => \DB\Common::CSMODE_UPDATE,
			];
			\Groups::save($params);
			$newGroup = \Groups::get(['id' => $groupId]);
			\Logs::update_log(\Groups::LOGS_OBJECT, $groupId, $oldGroup, $newGroup,[
				'_save_fields' => ['id'],
			]);
			common_redirect('/admin/groups/');
		} else {
			// Create
			if (!$this->can_create_group) {
				$this->error = \T::Framework_Errors_PermissionDenied();
				return;
			}
			$params = [
				'title' => $groupTitle,
				'create_time' => time(),
				'update_time' => time(),
				'_mode' => \DB\Common::CSMODE_INSERT,
			];
			$groupId = \Groups::save($params);
			$newGroup = \Groups::get(['id' => $groupId]);
			\Logs::create_log(\Groups::LOGS_OBJECT, $groupId, $newGroup);
			if ($groupId > 0) {
				foreach(\Groups::permissions_hash() as $permission=>$_) {
					$ObjectPermission = [
						'object' => 'user',
						'object_id' => intval(\Sessions::currentUser()['id']),
						'subject' => $permission,
						'subject_id' => $groupId,
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
					$ObjectPermission['id'] = \ObjectPermissions::save($ObjectPermission);
					$log_id = \Logs::create_log(\ObjectPermissions::LOGS_OBJECT, $ObjectPermission['id'], $ObjectPermission);
					\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, $groupId);
				}
				common_redirect('/admin/groups/');
			}
		}
	}

	private function delete($group_id) {
		if (!$group_id) {
			$this->error = \T::Framework_Groups_Delete_GroupNotFound();
			return;
		}
		if (!\Sessions::checkPermission(\Groups::PERMISSION_MANAGE_GROUPS, $group_id, DELETE)) {
			$this->error = \T::Framework_Groups_Delete_PermissionDenied();
			return;
		}
		$group = \Groups::get(['id' => $group_id]);
		if (!$group) {
			$this->error = \T::Framework_Groups_Delete_GroupNotFound();
			return;
		}
		if (in_array($group_id, [\Groups::ADMIN_GROUP_ID, \Groups::DEFAULT_GROUP_ID])) {
			$this->error = \T::Framework_Groups_Delete_PermissionDenied();
			return;
		}
		$UserGroups = \UserGroups::data(['group_id' => $group_id]);
		if ($UserGroups) {
			foreach($UserGroups as $UserGroup) {
				\Users::clear_session_cache($UserGroup['user_id']);
				\UserGroups::delete(['id' => $UserGroup['id']]);
				$log_id = \Logs::delete_log(\UserGroups::LOGS_OBJECT, $UserGroup['id'], $UserGroup);
				\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $UserGroup['user_id']);
				\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, $UserGroup['group_id']);
			}
		}
		$sql = [];
		$sql[] = sql_pholder(" (`object` = 'group' AND `object_id` = ?) ", $group_id);
		foreach(\Groups::permissions_hash() as $permission=>$_) {
			$sql[] = sql_pholder(" (`subject` = ? AND `subject_id` = ?) ", $permission, $group_id);
		}
		$sql = ' AND ('.implode(' OR ', $sql).')';
		$ObjectPermissions = \ObjectPermissions::data(false, $sql);
		if ($ObjectPermissions) {
			foreach($ObjectPermissions as $ObjectPermission) {
				\ObjectPermissions::delete(['id' => $ObjectPermission['id']]);
				$log_id = \Logs::delete_log(\ObjectPermissions::LOGS_OBJECT, $ObjectPermission['id'], $ObjectPermission);
				\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, $group_id);
				if ($ObjectPermission['object'] == 'user') {
					\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $ObjectPermission['object_id']);
				} elseif ($ObjectPermission['object'] == 'group' && $ObjectPermission['object_id'] != $group_id) {
					\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, $ObjectPermission['object_id']);
				}
			}
		}
		\Groups::delete(['id' => $group_id]);
		\Logs::delete_log(\Groups::LOGS_OBJECT, $group_id, $group);
		common_redirect('/admin/groups/');
	}


	private function check_permissions() {
		\Sessions::requestPermission(\Permissions::ADMIN, 0, READ);

		$this->can_read_global = \Sessions::checkPermission(\Groups::PERMISSION_MANAGE_GROUPS, -1, READ);
		$this->can_delete = \Sessions::checkPermission(\Groups::PERMISSION_MANAGE_GROUPS, -1, DELETE);
		$this->can_create_group = \Sessions::checkPermission(\Groups::PERMISSION_CREATE_GROUP, 0, WRITE);

		if (!$this->can_read_global && !$this->can_create_group) {
			e403();
		}
	}

	private function get_data() {
		$manageGroups = [];
		$sql = '';
		if (!$this->can_read_global) {
			$manageGroups = \Sessions::getAllSubjectPermissions(\Groups::PERMISSION_MANAGE_GROUPS);
			if ($manageGroups) {
				$sql .= sql_pholder(' AND id IN (?@)', array_keys($manageGroups));
			} else {
				$sql .= sql_pholder(' AND 0');
			}
		}
		$this->groups = \Groups::data(false, $sql, 'id, title, create_time');

		$this->groups_counts_hash = get_hash(\UserGroups::data(false, ' GROUP BY group_id ORDER BY NULL', 'group_id, count(*) as users_count'), 'group_id', 'users_count');
	}

	private function print_header() {
		?>
		<div class="float-start"><h2><i class="bi bi-people"></i> <?=\T::Framework_Menu_Groups();?></h2></div>
		<?php if ($this->can_create_group) {
			?>
			<div class="float-end">
				<h3 class="pointer text-info">
					<i class="bi bi-plus-circle createGroupAction"> <?=\T::Framework_Common_Create();?></i>
				</h3>
			</div>
			<?php
		}
		?>
		<div class="clearfix"></div>
		<?php
	}

	private function print_table() {
		?>
		<div class="row d-flex justify-content-center">
			<table class="table table-transparent table-responsive" id="GroupsTable">
				<thead class="">
					<tr>
					<th scope="col" class="align-middle" data-priority="1">ID</th>
					<th scope="col" class="align-middle" data-priority="2"><?=\T::Framework_Groups_Table_Title();?></th>
					<th scope="col" class="align-middle text-center" data-priority="4"><?=\T::Framework_Groups_Table_UsersCount();?></th>
					<th scope="col" class="align-middle text-center" data-priority="5"><?=\T::Framework_Common_CreateTime();?></th>
					<th scope="col" class="align-middle text-center" data-priority="3"><?=\T::Framework_Groups_Table_Permissions();?></th>
					<?php if ($this->can_delete) {?>
						<th scope="col" class="align-middle text-center" data-priority="3"><?=\T::Framework_Groups_Table_Delete();?></th>
					<?php } ?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($this->groups as $group) {
						$params = [
							'id' => intval($group['id'] ?? 0),
							'title' => htmlspecialchars(trim($group['title'] ?? '')),
							'users_count' => intval($this->groups_counts_hash[$group['id']] ?? 0),
							'create_time' => isset($group['create_time']) && $group['create_time'] > 0 ? date("Y-m-d H:i:s", $group['create_time']) : '',
						];

						$can_read_group = \Sessions::checkPermission(\Groups::PERMISSION_MANAGE_GROUPS, $params['id'], READ);
						$can_write_group = \Sessions::checkPermission(\Groups::PERMISSION_MANAGE_GROUPS, $params['id'], WRITE);
						$can_delete_group = \Sessions::checkPermission(\Groups::PERMISSION_MANAGE_GROUPS, $params['id'], DELETE);

						if (in_array($params['id'], [\Groups::ADMIN_GROUP_ID, \Groups::DEFAULT_GROUP_ID])) {
							$can_read_group = true;
							$can_write_group = false;
							$can_delete_group = false;
						}
						if (!$can_read_group) continue;

						$can_read_permissions = \Sessions::checkPermission(\Groups::PERMISSION_MANAGE_GROUP_PERMISSIONS, $params['id'], READ);
						$can_write_permissions = \Sessions::checkPermission(\Groups::PERMISSION_MANAGE_GROUP_PERMISSIONS, $params['id'], WRITE);

						?>
						<tr>
							<th scope="row" class="align-middle"><?=$params['id'];?></th>
							<td class="align-middle">
								<?php
								if ($can_write_group) {
									?>
									<span class="d-inline pointer text-info editGroupAction"
										data-group-id = "<?=$params['id'];?>"
										data-group-title = "<?=addslashes($params['title']);?>"
									>
										<?=$params['title'];?>
									</span>
									<?php
								} else {
									?>
									<span class="d-inline">
										<?=$params['title'];?>
									</span>
									<?php
								}
								?>
							</td>
							<td class="align-middle text-center"><?=$params['users_count'];?></td>
							<td class="align-middle text-center"><?=$params['create_time'];?></td>
							<td class="align-middle text-center">
								<?php
									if (!in_array($params['id'], [\Groups::ADMIN_GROUP_ID]) && ($can_read_permissions || $can_write_permissions)) {
										?>
										<a href="/admin/permissions/group/<?=$params['id'];?>/"><i class="bi bi-file-earmark-lock fs-4 text-info pointer"></i></a>
										<?php
									} else {
										?><i class="bi bi-file-earmark-lock fs-4 text-secondary"></i><?php
									}
								?>
							</td>
							<?php if ($this->can_delete) {?>
								<td class="align-middle text-center">
									<?php
									if ($can_delete_group) {
										?>
										<i class="bi bi-trash fs-4 text-danger pointer deleteGroupAction"
											data-group-id = "<?=$params['id'];?>"
											data-group-title = "<?=addslashes($params['title']);?>"
										></i>
										<?php
									} else {
										?><i class="bi bi-trash fs-4 text-secondary"></i><?php
									}
									?>
								</td>
							<?php } ?>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>

			<?php
			// $template->pagination($page, $groups_count);
			?>

		</div>
		<?php
	}

	private function print_modal() {
		?>
		<div class="modal" id="deleteGroupModal" tabindex="-1" role="dialog" aria-labelledby="deleteGroupModalLabel" aria-modal="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
				<div class="modal-header border-secondary">
					<h5 class="modal-title" id="deleteGroupModalLabel"><?=\T::Framework_Groups_Delete_Title();?></h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?=\T::Framework_Common_Close();?>" title="<?=\T::Framework_Common_Close();?>"></button>
				</div>
				<div class="modal-body" id="deleteGroupModalBody">
					<?=\T::Framework_Groups_Delete_Confirmation();?>
				</div>
				<div class="modal-footer border-secondary">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?=\T::Framework_Common_Cancel();?></button>
					<button type="button" class="btn btn-danger" id="confirmDeleteBtn"><?=\T::Framework_Common_Delete();?></button>
				</div>
				</div>
			</div>
		</div>

		<div class="modal" id="createGroupModal" tabindex="-1" role="dialog" aria-labelledby="createGroupModalLabel" aria-modal="true">
			<div class="modal-dialog modal-fullscreen-md-down" role="document">
				<div class="modal-content">
					<form action="/admin/groups/save/" class="needs-validation" method="post" novalidate>
						<div class="modal-header border-secondary">
							<h5 class="modal-title" id="createGroupModalLabel"><?=\T::Framework_Groups_ModalTitle();?></h5>
							<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?=\T::Framework_Common_Close();?>" title="<?=\T::Framework_Common_Close();?>"></button>
						</div>
						<div class="modal-body" id="createGroupModalBody">
							<input type="hidden" name="id" value="" id="createGroupId" >
							<div class="mb-3 row">
								<label for="createGroupTitle" class="col-sm-3 col-form-label"><?=\T::Framework_Groups_Create_Title();?><sup>*</sup></label>
								<div class="col-sm-9">
									<input type="text" required class="form-control" id="createGroupTitle" name="title" value="">
									<div class="valid-feedback">
										<?=\T::Framework_Common_FormLooksGood();?>
									</div>
									<div class="invalid-feedback">
										<?=\T::Framework_Common_FormRequired();?>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer border-secondary">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?=\T::Framework_Common_Cancel();?></button>
							<button type="submit" class="btn btn-success" id="confirmCreateBtn" name="action" value="save"><?=\T::Framework_Common_Create();?></button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<?php
	}

	private function print_javascript() {
		?>
		<script nonce="<?=\CSP::nonceRandom();?>">
			$(document).ready(function(){
				new DataTable('#GroupsTable');

				$('.createGroupAction').on('click', function() {
					showCreateGroupModal('', '');
				});

				$('.editGroupAction').on('click', function() {
					showCreateGroupModal($(this).data('group-id'), $(this).data('group-title'));
				});

				$('.deleteGroupAction').on('click', function() {
					console.log($(this).data());
					showDeleteGroupModal($(this).data('group-id'), $(this).data('group-title'));
				});
			});

			function showDeleteGroupModal(groupId, groupTitle) {
				const modal = new bootstrap.Modal(document.getElementById('deleteGroupModal'));
				const modalBody = document.getElementById('deleteGroupModalBody');
				const confirmBtn = document.getElementById('confirmDeleteBtn');

				modalBody.textContent = '<?=\T::Framework_Groups_Delete_Confirmation();?>'.replace('{group}', groupTitle);

				confirmBtn.onclick = function() {
					window.location.href = '/admin/groups/delete/' + groupId + '/';
				};

				modal.show();

				const modalElement = document.getElementById('deleteGroupModal');
				modalElement.addEventListener('hide.bs.modal', event => {
					const focusedElement = document.activeElement;
					if (modalElement.contains(focusedElement)) {
						focusedElement.blur();
					}
				});
			}

			function showCreateGroupModal(groupId, groupTitle) {
				const modal = new bootstrap.Modal(document.getElementById('createGroupModal'));
				const createGroupId = document.getElementById('createGroupId');
				const createGroupTitle = document.getElementById('createGroupTitle');

				createGroupId.value = groupId;
				createGroupTitle.value = groupTitle;

				if (createGroupId.value) {
					confirmCreateBtn.textContent = '<?=\T::Framework_Common_Save();?>';
				} else {
					confirmCreateBtn.textContent = '<?=\T::Framework_Common_Create();?>';
				}

				modal.show();

				const modalElement = document.getElementById('createGroupModal');
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
