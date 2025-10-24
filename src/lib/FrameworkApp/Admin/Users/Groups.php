<?php
namespace FrameworkApp\Admin\Users;

class Groups extends \Routing_Parent implements \Routing_Interface {

	private string $url = '?';
	private array $user = [];
	private int $user_id = 0;
	private bool $can_read = false;
	private bool $can_write = false;
	private bool $can_delete = false;
	private array $admin_active_user_ids = [];
	private array $groups = [];
	private array $groups_hash = [];
	private array $user_groups = [];


	public function Run($user_id=0, $action='list', $group_id=0) {
		$this->user_id = intval($user_id??0);

		$this->check_auth();
		$this->check_permissions();

		$this->get_admin_user_ids();

		if ($action == 'delete') {
			$this->processDeleteAction($group_id ?? 0);
		} elseif ($action == 'save') {
			$this->processSaveAction($_POST['groups-select'] ?? 0);
		}

		$this->get_data();
		$this->template = new \Template();
		$this->print_header();

		$this->print_table();
		$this->print_modals();
		$this->print_javascript();
	}

	private function processDeleteAction(int $groupId) {
		if (!$this->can_delete) {
			$this->error = \T::Framework_Errors_PermissionDenied();
			return;
		}
		if (!$this->user_id) {
			$this->error = \T::Framework_Errors_PermissionDenied();
			return;
		}
		if (!\Sessions::checkPermission(\Groups::PERMISSION_MANAGE_GROUPS, $groupId, READ)) {
			$this->error = \T::Framework_Errors_PermissionDenied();
			return;
		}
		if (!$groupId) {
			$this->error = \T::Framework_Groups_Delete_GroupNotFound();
			return;
		}
		$group = \Groups::get(['id' => $groupId]);
		if (!$group) {
			$this->error = \T::Framework_Groups_Delete_GroupNotFound();
			return;
		}
		if ($groupId == \Groups::DEFAULT_GROUP_ID) {
			$this->error = \T::Framework_Groups_Delete_DefaultGroupDenied();
			return;
		} elseif ($groupId == \Groups::ADMIN_GROUP_ID) {
			if (count($this->admin_active_user_ids) < 2) {
				$this->error = \T::Framework_Groups_Delete_AdminGroupDenied();
				return;
			}
		}
		$old = \UserGroups::get(['user_id' => $this->user_id, 'group_id' => $groupId]);
		if (!$old) {
			$this->error = \T::Framework_Groups_Delete_GroupNotFound();
			return;
		}
		\UserGroups::delete($old['id']);
		\Users::clear_session_cache($this->user_id);
		$log_id = \Logs::delete_log(\UserGroups::LOGS_OBJECT, $old['id'], $old);
		\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, $groupId);
		\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $this->user_id);
		common_redirect($this->url);
	}

	private function processSaveAction(int $groupId) {
		if (!$this->can_write) {
			$this->error = \T::Framework_Errors_PermissionDenied();
			return;
		}
		if (!$this->user_id) {
			$this->error = \T::Framework_Errors_PermissionDenied();
			return;
		}
		if (!$groupId) {
			$this->error = \T::Framework_Groups_Create_GroupNotFound();
			return;
		}
		$group = \Groups::get(['id' => $groupId]);
		if (!$group) {
			$this->error = \T::Framework_Groups_Delete_GroupNotFound();
			return;
		}
		$old = \UserGroups::get(['user_id' => $this->user_id, 'group_id' => $groupId]);
		if ($old) {
			$this->error = \T::Framework_Groups_Create_Error();
			return;
		}
		$params = [
			'user_id' => $this->user_id,
			'group_id' => $groupId,
			'create_time' => time(),
			'update_time' => time(),
			'_mode' => \DB\Common::CSMODE_INSERT,
		];
		$params['id'] = \UserGroups::save($params);
		if ($params['id'] <= 0) {
			$this->error = \T::Framework_Groups_Create_Error();
			return;
		}
		$log_id = \Logs::create_log(\UserGroups::LOGS_OBJECT, $params['id'], $params);
		\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, $groupId);
		\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $this->user_id);
		common_redirect($this->url);
	}

	private function check_permissions() {
		\Sessions::requestPermission(\Permissions::ADMIN, 0, READ);

		$this->user = \Users::get(['id' => $this->user_id]);
		if ($this->user) {
			$this->can_read = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USER_GROUPS, $this->user_id, READ);
			$this->can_write = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USER_GROUPS, $this->user_id, WRITE);
			$this->can_delete = \Sessions::checkPermission(\Users::PERMISSION_MANAGE_USER_GROUPS, $this->user_id, DELETE);
		}
		$this->url = '/admin/users/'.intval($this->user_id).'/groups/';

		if (!$this->can_read || !$this->user_id || !$this->user) {
			e403();
		}
	}

	private function get_admin_user_ids() {
		$admin_user_ids = array_values(get_hash(\UserGroups::data(['group_id' => \Groups::ADMIN_GROUP_ID],'','user_id'), 'user_id', 'user_id'));
		$this->admin_active_user_ids = $admin_user_ids ? array_values(get_hash(\Users::data(['id' => $admin_user_ids, 'status' => \Users::STATUS_ACTIVE],'','id'),'id','id')) : [];

	}

	private function get_data() {
		$this->user_groups = \UserGroups::data(false, sql_pholder(' AND `user_id`=?',$this->user_id));
		$data = \Groups::data(false, '', 'id, title, create_time');
		$this->groups = [];
		$this->groups_hash = [];
		foreach($data as $item) {
			if (!isset($item['id']) && !$item['id']) continue;
			$can_read_group = \Sessions::checkPermission(\Groups::PERMISSION_MANAGE_GROUPS, $item['id'], READ);
			if (!$can_read_group) continue;
			$this->groups[$item['id']] = $item;
			$this->groups_hash[$item['id']] = $item['title']?? \T::Framework_Menu_Group().' ['.$item['id'].']';
		}
	}

	private function print_header() {
		?>
		<div class="float-start"><h2>
			<a href="/admin/users/" class="text-info"><i class="bi bi-arrow-left-circle"></i></a>
			<i class="bi bi-person"></i> <?=\T::Framework_Menu_UserGroups($this->user['name'], $this->user['surname'], $this->user['id']);?>
		</h2></div>
		<?php
			if ($this->can_write) {
				?>
				<div class="float-end">
					<h3 class="pointer text-info">
						<i class="bi bi-plus-circle addGroupsAction"> <?=\T::Framework_Common_Add();?></i>
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
			<div class="col-12 col-xl-10 col-xxl-10">
			<table class="table table-transparent table-responsive" id="GroupsTable">
				<thead class="">
					<tr>
						<th scope="col" class="align-middle">ID</th>
						<th scope="col" class="align-middle"><?=\T::Framework_Groups_Table_Title();?></th>
						<?php if ($this->can_delete) {?>
							<th scope="col" class="align-middle text-center"><?=\T::Framework_Groups_Table_Delete();?></th>
						<?php } ?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($this->user_groups as $userGroup) {
						$group = $this->groups[ $userGroup['group_id'] ?? 0 ] ?? [];
						if (!$group) continue;
						unset($this->groups_hash[$group['id']]);
						$params = [
							'id' => intval($group['id'] ?? 0),
							'title' => htmlspecialchars(trim($group['title'] ?? '')),
							'create_time' => isset($group['create_time']) && $group['create_time'] > 0 ? date("Y-m-d H:i:s", $group['create_time']) : '',
						];
						$can_delete_group = true;
						if ($params['id'] == \Groups::DEFAULT_GROUP_ID) {
							$can_delete_group = false;
						} elseif ($params['id'] == \Groups::ADMIN_GROUP_ID) {
							if (count($this->admin_active_user_ids) < 2) {
								$can_delete_group = false;
							}
						}
						?>
						<tr>
							<th scope="row" class="align-middle"><?=$params['id'];?></th>
							<td class="align-middle">
								<span class="d-inline">
									<?=$params['title'];?>
								</span>
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
			</div>
		</div>
		<?php
	}

	private function print_modals() {
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
					<form action="<?=$this->url;?>save/" class="needs-validation" method="post" novalidate>
						<div class="modal-header border-secondary">
							<h5 class="modal-title" id="createGroupModalLabel"><?=\T::Framework_Groups_ModalTitle();?></h5>
							<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?=\T::Framework_Common_Close();?>" title="<?=\T::Framework_Common_Close();?>"></button>
						</div>
						<div class="modal-body" id="createGroupModalBody">
							<?php
							echo $this->template?->html_select('groups-select', $this->groups_hash, 0, \T::Framework_Menu_Groups(), true,[
								'with-undefined' => false,
								'data-container' => '#createGroupModal',
							]);
							?>
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

				$('.addGroupsAction').on('click', function() {
					showCreateGroupModal();
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
					window.location.href = '<?=$this->url;?>delete/' + groupId + '/';
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
			function showCreateGroupModal() {
				const modal = new bootstrap.Modal(document.getElementById('createGroupModal'));
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