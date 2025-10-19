<?php
namespace FrameworkApp\Admin;

class Permissions extends \Routing_Parent implements \Routing_Interface {

	private string $url = '/admin/permissions/';
	private string $subject = '';
	private int $subject_id = 0;

	private array $group = [];
	private int $group_id = 0;
	private array $user = [];
	private $user_id = 0;

	private bool $can_read = false;
	private bool $can_write = false;

	private array $access_data_codes = [
		0 => '0',
		1 => '1',
		2 => '2',
	];

	private array $permissions_from_group = [];
	private array $permissions = [];

	private bool $access_change = false;

	public function Run($subject=null, $subject_id=null) {
		$this->subject = strval($subject??'');
		$this->subject_id = intval($subject_id??0);
		$this->check_auth();
		$this->check_permissions();

		if (isset($_POST) && $_POST) {
			if (isset($_POST['method']) && $_POST['method'] == 'ajaxSubjects') {
				$this->ajaxSubjects($_POST['subjectType']);
				exit;
			}

			if (isset($_POST['action']) && $_POST['action'] == 'create') {
				$this->create($_POST);
			}
		}

		$this->get_data();

		if (isset($_POST) && $_POST) {
			if (isset($_POST['action']) && $_POST['action'] && $_POST['action'] == 'save') {
				$this->save($_POST);
			}
		}

		$this->access_change();

		$this->template = new \Template();
		$this->print_header();
		$this->print_table();
		$this->print_modal();
		$this->print_javascript();

	}

	private function ajaxSubjects(string $subjectType=''):void {
		echo '<option value="">-- None --</option>';
		if (!isset($subjectType)) {
			return;
		}
		if (!$subjectType) {
			return;
		}
		if (!\Permissions::get_subject_classes()[$subjectType]) {
			return;
		}
		$objectClass = \Permissions::get_subject_classes()[$subjectType] ?? '';
		if (!$objectClass) return;

		$data = $objectClass::subject_hash();
		if (!isset($data) || !$data || !is_array($data)) return;
		foreach($data as $key=>$value) {
			echo '<option value="'.$key.'">'.$value.'</option>';
		}
		return;
	}

	private function create($data) {
		if (!$this->can_write) {
			return;
		}
		if (!isset($data['permissionsType'])) {
			return;
		}

		$params = [];
		if ($this->group_id > 0) {
			$params['object'] = 'group';
			$params['object_id'] = $this->group_id;
		} elseif ($this->user_id > 0) {
			$params['object'] = 'user';
			$params['object_id'] = $this->user_id;
		} else {
			return;
		}
		if (!$params['object'] || !$params['object_id']) {
			return;
		}
		$subjects = [];
		if (!\Permissions::get_subject_classes()[$data['permissionsType']]) {
			return;
		}
		$objectClass = \Permissions::get_subject_classes()[$data['permissionsType']] ?? '';
		if (!$objectClass) return;

		$subject_hash = $objectClass::subject_hash();
		if (!isset($subject_hash) || !$subject_hash || !is_array($subject_hash)) return;
		$params['subject_id'] = intval($data['subject-select'] ?? 0);
		if (!$params['subject_id']) {
			return;
		}
		var_dump($params);
		if (!isset($subject_hash[$params['subject_id']])) {
			return;
		}
		$subjects = array_keys($objectClass::permissions_hash());
		if (!$subjects) {
			return;
		}
		foreach($subjects as $subject) {
			$params['subject'] = $subject;
			$old_permission = \ObjectPermissions::get($params);
			if ($old_permission) {
				continue;
			}
			$param = $params;
			$param['create_time'] = time();
			$param[READ] = 0;
			$param[WRITE] = 0;
			$param[DELETE] = 0;
			$param[ACCESS_READ] = 0;
			$param[ACCESS_WRITE] = 0;
			$param[ACCESS_CHANGE] = 0;
			$param['_mode'] = \DB\Common::CSMODE_INSERT;
			$new_id = \ObjectPermissions::save($param);
			if ($new_id <= 0) {
				break;
			}
			$new_permission = \ObjectPermissions::get($new_id);
			$log_id = \Logs::create_log(\ObjectPermissions::LOGS_OBJECT, $new_id, $new_permission);
			if ($new_permission['object'] == 'group') {
				\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, $new_permission['object_id']);
			} else if ($new_permission['object'] == 'user') {
				\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $new_permission['object_id']);
			}
		}
		common_redirect($this->url);
	}

	private function save($data) {
		$group_id = intval($data['group_id'] ?? 0);
		$user_id = intval($data['user_id'] ?? 0);
		$subject = strval($data['subject'] ?? '');
		$subject_id = intval($data['subject_id'] ?? 0);
		$accessCode = strval($data['access'] ?? '');
		$value = intval($data['value'] ?? 0);
		$return = [];
		$new_id = 0;
		do {
			if (!$this->can_write) break;
			if (!$group_id && !$user_id) break;
			if ($group_id && in_array($group_id, [\Groups::ADMIN_GROUP_ID])) break;
			if (isset($this->group_id) && $this->group_id > 0 && $this->group_id != $group_id && $group_id > 0) break;
			if (isset($this->user_id) && $this->user_id > 0 && $this->user_id != $user_id && $user_id > 0) break;
			if (!$subject) break;
			if (!$accessCode) break;
			if (!\Sessions::checkPermission($subject, $subject_id, ACCESS_WRITE)) break;

			if ($group_id) {
				$object = 'group';
				$object_id = $group_id;
			} elseif ($user_id) {
				$object = 'user';
				$object_id = $user_id;
			} else {
				break;
			}

			if (in_array($accessCode, [READ, WRITE, DELETE])) {
				if (!\Sessions::checkPermission($subject, $subject_id, ACCESS_WRITE)) break;
			} elseif (in_array($accessCode, [ACCESS_READ, ACCESS_WRITE, ACCESS_CHANGE])) {
				if (!\Sessions::checkPermission($subject, $subject_id, ACCESS_CHANGE)) break;
			}

			$old_perm = \ObjectPermissions::get([
				'object' => $object,
				'object_id' => $object_id,
				'subject' => $subject,
				'subject_id' => $subject_id
			]);
			$param = [
				'object' => $object,
				'object_id' => $object_id,
				'subject' => $subject,
				'subject_id' => $subject_id,
				$accessCode => $value,
				'update_time' => time(),
			];
			if ($old_perm) {
				$param['id'] = $old_perm['id'];
				$param['_mode'] = \DB\Common::CSMODE_UPDATE;
			} else {
				$param['create_time'] = time();
				$param['_mode'] = \DB\Common::CSMODE_INSERT;
			}
			$new_id = \ObjectPermissions::save($param);
			if ($new_id <= 0) break;
			$new_permission = \ObjectPermissions::get($new_id);
			$log_id = 0;
			if ($old_perm) {
				$log_id = \Logs::update_log(\ObjectPermissions::LOGS_OBJECT, $new_id, $old_perm, $new_permission);
			} else {
				$log_id = \Logs::create_log(\ObjectPermissions::LOGS_OBJECT, $new_id, $new_permission);

			}
			if ($log_id) {
				if ($new_permission['object'] == 'group') {
					\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, $new_permission['object_id']);
				} else if ($new_permission['object'] == 'user') {
					\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $new_permission['object_id']);
				}
			}
			$ObjectPermissions = \ObjectPermissions::data(false, sql_pholder('
					AND `object` = ?
					AND `object_id` = ?
					AND `subject` = ?
					AND `subject_id` = ?
					AND `'.READ.'` = 0
					AND `'.WRITE.'` = 0
					AND `'.DELETE.'` = 0
					AND `'.ACCESS_READ.'` = 0
					AND `'.ACCESS_WRITE.'` = 0
					AND `'.ACCESS_CHANGE.'` = 0 '
					,$object, $object_id, $subject, $subject_id));
			if ($ObjectPermissions) {
				foreach($ObjectPermissions as $item) {
					\ObjectPermissions::delete($item['id']);
					$log_id = \Logs::create_log(\ObjectPermissions::LOGS_OBJECT, $item['id'], $item);
					if ($item['object'] == 'group') {
						\Logs::add_tag($log_id, \Groups::LOGS_OBJECT, $item['object_id']);
					} else if ($item['object'] == 'user') {
						\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $item['object_id']);
					}
				}
			}
		} while (false);
		if ($new_id <= 0) {
			$value = $value - 1;
			if ($value < 0) {
				$value = 2;
			}
		}
		$return['status'] = $new_id;
		$return['class'] = $this->access_classes()[$value];
		$return['data_code'] = $this->access_data_codes[$value];
		$return['title'] = $this->access_titles()[$value];
		echo json_encode($return);
		exit;
	}

	private function check_permissions() {
		\Sessions::requestPermission(\Permissions::ADMIN, 0, READ);

		if (!$this->subject || !$this->subject_id) {
			e403();
		}

		if (!isset(\Permissions::permissions_type()[$this->subject])) {
			e403();
		}

		if ($this->subject == 'group') {
			$this->group_id = $this->subject_id;
			$this->group = \Groups::get(['id' => $this->group_id]);
			if ($this->group && !in_array($this->group_id, [\Groups::ADMIN_GROUP_ID])) {
				$this->can_read = \Sessions::checkPermission(\Permissions::MANAGE_GROUP_PERMISSIONS, $this->group_id, READ);
				$this->can_write = \Sessions::checkPermission(\Permissions::MANAGE_GROUP_PERMISSIONS, $this->group_id, WRITE);
			}
			$this->url .= 'group/'.intval($this->group_id).'/';
		} elseif ($this->subject == 'user') {
			$this->user_id = $this->subject_id;
			$this->user = \Users::get(['id' => $this->user_id]);
			if ($this->user) {
				$this->can_read = \Sessions::checkPermission(\Permissions::MANAGE_USER_PERMISSIONS, $this->user_id, READ);
				$this->can_write = \Sessions::checkPermission(\Permissions::MANAGE_USER_PERMISSIONS, $this->user_id, WRITE);
			}
			$this->url .= 'user/'.intval($this->user_id).'/';
		}
		if (!$this->can_read) {
			e403();
		}
	}

	private function access_classes(): array {
		return [
			0 => ($this->can_write ? 'access-checkpoint pointer':'disabled').' bi bi-square fs-4',
			1 => ($this->can_write ? 'access-checkpoint pointer':'disabled').' bi bi-check-square text-info fs-4',
			2 => ($this->can_write ? 'access-checkpoint pointer':'disabled').' bi bi-x-square text-danger fs-4',
		];
	}

	private function access_titles(): array {
		return [
			0 => \T::Framework_Permission_access_0(),
			1 => \T::Framework_Permission_access_1(),
			2 => \T::Framework_Permission_access_2(),
		];
	}

	private function access(): array {
		return [
			0 => '<i data-code="'.$this->access_data_codes[0].'" class="'.$this->access_classes()[0].'" title="'.$this->access_titles()[0].'"></i>',
			1 => '<i data-code="'.$this->access_data_codes[1].'" class="'.$this->access_classes()[1].'" title="'.$this->access_titles()[1].'"></i>',
			2 => '<i data-code="'.$this->access_data_codes[2].'" class="'.$this->access_classes()[2].'" title="'.$this->access_titles()[2].'"></i>',
		];
	}

	private function get_data() {
		$this->permissions_from_group = [];
		$permissions_data = [];
		if ($this->group_id) {
			$permissions_data = \ObjectPermissions::data(false, sql_pholder(' AND `object`="group" AND `object_id`=?',$this->group_id));
		} elseif ($this->user_id) {
			$permissions_data = \ObjectPermissions::data(false, sql_pholder(' AND `object`="user" AND `object_id`=?',$this->user_id));
			$user['groups'] = \Sessions::getUserGroups($this->user_id);
			$groupIds = [];
			if (isset($user['groups']) && is_array($user['groups']) && $user['groups']) {
				$groupIds = array_keys($user['groups']);
			}
			$this->permissions_from_group = \Sessions::getGroupsPermissions($groupIds);
		} else {
			e403();
		}

		// Все права пользователя или группы из базы
		$permissions_hash = [];
		foreach($permissions_data as $item) {
			$permissions_hash[$item['subject']][$item['subject_id']] = $item;
		}

		$this->permissions = [];
		foreach (\Permissions::permissions_hash() as $subject=>$permissionTitle) {
			$subject_id=0;
			$permission = $permissions_hash[$subject]??[];
			$permission = $permission[$subject_id]??[];
			$this->permissions[$subject.'_'.$subject_id] = [
				'subject' => $subject,
				'subject_id' => $subject_id,
				'title' => $permissionTitle,
				'permission' => $permission,
			];
		}
		$subject_types_hash = \Permissions::subject_types_hash();
		foreach(\Permissions::get_subject_classes() as $subjectType=>$subjectClass) {
			$subject_permissions_hash = $subjectClass::permissions_hash();

			foreach($subjectClass::permissions_subject_hash() as $subject_id=>$permissionTitle) {
				foreach($subject_permissions_hash as $subject=>$subjectTitle) {
					$permission = $permissions_hash[$subject]??[];
					$permission = $permission[$subject_id]??[];
					if ($permission) {
						$this->permissions[$subject.'_'.$subject_id] = [
							'subject' => $subject,
							'subject_id' => $subject_id,
							'title' => sprintf('%s &laquo;%s [%d]&raquo;: %s', $subject_types_hash[$subjectType]??$subjectType, htmlspecialchars($permissionTitle), $subject_id, $subjectTitle),
							'permission' => $permission,
						];
					}
				}
			}
		}
	}

	/**
	 * @SuppressWarnings(PHPMD)
	 */
	private function print_header() {
		?>
		<div class="float-start">
			<h1><i class="bi bi-file-earmark-lock"></i>
			<?php if ($this->group_id && $this->group) {
				echo \T::Framework_Menu_GroupPermissions($this->group['title'], $this->group['id']);
			} elseif ($this->user_id && $this->user) {
				echo \T::Framework_Menu_UserPermissions($this->user['name'], $this->user['surname'], $this->user['id']);
			}
			?>
		</div>
		<?php
			if ($this->can_write) {
				?>
				<div class="float-end">
					<h3 class="pointer text-info">
						<i class="bi bi-plus-circle addPermissionAction"> <?=\T::Framework_Common_Add();?></i>
					</h3>
				</div>
				<?php
			}
		?>
		<div class="clearfix"></div>
		<?php
	}

	private function access_change() {
		$this->access_change = false;
		foreach(\Sessions::currentUser()['permissions'] as $v) {
			foreach($v as $v2) {
				if (isset($v2['access_change']) && $v2['access_change'] == 1) {
					$this->access_change = true;
					break;
				}
			}
		}
		if (\Sessions::in_group(\Groups::ADMIN_GROUP_ID)) $this->access_change = true;

	}

	private function print_table() {
		?>
		<div class="row d-flex justify-content-center">
			<table class="table table-transparent table-responsive" id="permissionsTable">
				<thead>
					<tr>
						<th scope="col" class="align-middle"><?=\T::Framework_Permission_Table_Title();?></th>

						<th scope="col" class="align-middle text-center" title="<?=\T::Framework_Permission_Table_Read();?>">
							<div class="d-none d-md-table-cell"><?=\T::Framework_Permission_Table_Read();?></div>
							<div class="d-table-cell d-md-none">R</div>
						</th>
						<th scope="col" class="align-middle text-center" title="<?=\T::Framework_Permission_Table_Write();?>">
							<div class="d-none d-md-table-cell"><?=\T::Framework_Permission_Table_Write();?></div>
							<div class="d-table-cell d-md-none">W</div>
						</th>
						<th scope="col" class="align-middle text-center" title="<?=\T::Framework_Permission_Table_Delete();?>">
							<div class="d-none d-md-table-cell"><?=\T::Framework_Permission_Table_Delete();?></div>
							<div class="d-table-cell d-md-none">D</div>
						</th>
						<?php
						if ($this->access_change) {
							?>
							<th scope="col" class="align-middle text-center" title="<?=\T::Framework_Permission_Table_AccessRead();?>">
								<div class="d-none d-md-table-cell"><?=\T::Framework_Permission_Table_AccessRead();?></div>
								<div class="d-table-cell d-md-none">AR</div>
							</th>
							<th scope="col" class="align-middle text-center" title="<?=\T::Framework_Permission_Table_AccessWrite();?>">
								<div class="d-none d-md-table-cell"><?=\T::Framework_Permission_Table_AccessWrite();?></div>
								<div class="d-table-cell d-md-none">AW</div>
							</th>
							<th scope="col" class="align-middle text-center" title="<?=\T::Framework_Permission_Table_AccessChange();?>">
								<div class="d-none d-md-table-cell"><?=\T::Framework_Permission_Table_AccessChange();?></div>
								<div class="d-table-cell d-md-none">AC</div>
							</th>
							<?php
						}
						?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($this->permissions as $item) {
						$title = $item['title'];
						$subject = $item['subject'];
						$subject_id = $item['subject_id'];
						$permission = $item['permission'];

						if ($this->permissions_from_group) {
							if (!isset($this->permissions_from_group[$subject])) {
								$this->permissions_from_group[$subject] = [];
							}
							if (!isset($this->permissions_from_group[$subject][$subject_id])) {
								$this->permissions_from_group[$subject][$subject_id] = [
									READ => 0,
									WRITE => 0,
									DELETE => 0,
									ACCESS_READ => 0,
									ACCESS_WRITE => 0,
									ACCESS_CHANGE => 0,
								];
							}
						}

						$params = [
							'title' => trim($title ?? ''),
							READ => max(min(2, intval($permission[READ] ?? 0)), 0),
							WRITE => max(min(2, intval($permission[WRITE] ?? 0)), 0),
							DELETE => max(min(2, intval($permission[DELETE] ?? 0)), 0),
							ACCESS_READ => max(min(2, intval($permission[ACCESS_READ] ?? 0)), 0),
							ACCESS_WRITE => max(min(2, intval($permission[ACCESS_WRITE] ?? 0)), 0),
							ACCESS_CHANGE => max(min(2, intval($permission[ACCESS_CHANGE] ?? 0)), 0),
						];

						$params[READ.'_icon'] = $this->access()[$params[READ]];
						$params[WRITE.'_icon'] = $this->access()[$params[WRITE]];
						$params[DELETE.'_icon'] = $this->access()[$params[DELETE]];
						$params[ACCESS_READ.'_icon'] = $this->access()[$params[ACCESS_READ]];
						$params[ACCESS_WRITE.'_icon'] = $this->access()[$params[ACCESS_WRITE]];
						$params[ACCESS_CHANGE.'_icon'] = $this->access()[$params[ACCESS_CHANGE]];

						$can_read_permission = \Sessions::checkPermission($subject, $subject_id, ACCESS_READ);
						$can_write_permission = \Sessions::checkPermission($subject, $subject_id, ACCESS_WRITE);
						$can_change_permission = \Sessions::checkPermission($subject, $subject_id, ACCESS_CHANGE);
						if (!$can_read_permission) continue;

						$from_groups_classes = [
							0 => 'bi bi-square invisible text-secondary fs-4',
							1 => 'bi bi-check text-info fs-4',
							2 => 'bi bi-x text-danger fs-4',
						];
						$from_groups = [
							READ => $this->permissions_from_group ? '<i title="'.\T::Framework_Permission_FromGroups().'" class="'.($from_groups_classes[$this->permissions_from_group[$subject][$subject_id][READ]]).'"></i>':'',
							WRITE => $this->permissions_from_group ? '<i title="'.\T::Framework_Permission_FromGroups().'" class="'.($from_groups_classes[$this->permissions_from_group[$subject][$subject_id][WRITE]]).'"></i>':'',
							DELETE => $this->permissions_from_group ? '<i title="'.\T::Framework_Permission_FromGroups().'" class="'.($from_groups_classes[$this->permissions_from_group[$subject][$subject_id][DELETE]]).'"></i>':'',
							ACCESS_READ => $this->permissions_from_group ? '<i title="'.\T::Framework_Permission_FromGroups().'" class="'.($from_groups_classes[$this->permissions_from_group[$subject][$subject_id][ACCESS_READ]]).'"></i>':'',
							ACCESS_WRITE => $this->permissions_from_group ? '<i title="'.\T::Framework_Permission_FromGroups().'" class="'.($from_groups_classes[$this->permissions_from_group[$subject][$subject_id][ACCESS_WRITE]]).'"></i>':'',
							ACCESS_CHANGE => $this->permissions_from_group ? '<i title="'.\T::Framework_Permission_FromGroups().'" class="'.($from_groups_classes[$this->permissions_from_group[$subject][$subject_id][ACCESS_CHANGE]]).'"></i>':'',
						];
						?>
						<tr>
							<td class="align-middle"><?=$params['title'];?></td>
							<td class="<?=$can_write_permission ? '': 'disabled ';?>align-middle text-center"
								data-subject_id="<?=$subject_id;?>"
								data-subject="<?=$subject;?>"
								data-access="<?=READ;?>"
								data-search=""
							>
								<?=$from_groups[READ];?>
								<?=$params[READ.'_icon'];?>
							</td>
							<td class="<?=$can_write_permission ? '': 'disabled ';?>align-middle text-center"
								data-subject_id="<?=$subject_id;?>"
								data-subject="<?=$subject;?>"
								data-access="<?=WRITE;?>"
								data-search=""
							>
								<?=$from_groups[WRITE];?>
								<?=$params[WRITE.'_icon'];?>
							</td>
							<td class="<?=$can_write_permission ? '': 'disabled ';?>align-middle text-center"
								data-subject_id="<?=$subject_id;?>"
								data-subject="<?=$subject;?>"
								data-access="<?=DELETE;?>"
								data-search=""
							>
								<?=$from_groups[DELETE];?>
								<?=$params[DELETE.'_icon'];?>
							</td>
							<?php
								if ($this->access_change) {
								?>
								<td class="<?=$can_change_permission ? '': 'disabled ';?>align-middle text-center"
									data-subject_id="<?=$subject_id;?>"
									data-subject="<?=$subject;?>"
									data-access="<?=ACCESS_READ;?>"
									data-search=""
								>
									<?=$from_groups[ACCESS_READ];?>
									<?=$params[ACCESS_READ.'_icon'];?>
								</td>
								<td class="<?=$can_change_permission ? '': 'disabled ';?>align-middle text-center"
									data-subject_id="<?=$subject_id;?>"
									data-subject="<?=$subject;?>"
									data-access="<?=ACCESS_WRITE;?>"
									data-search=""
								>
									<?=$from_groups[ACCESS_WRITE];?>
									<?=$params[ACCESS_WRITE.'_icon'];?>
								</td>
								<td class="<?=$can_change_permission ? '': 'disabled ';?>align-middle text-center"
									data-subject_id="<?=$subject_id;?>"
									data-subject="<?=$subject;?>"
									data-access="<?=ACCESS_CHANGE;?>"
									data-search=""
								>
									<?=$from_groups[ACCESS_CHANGE];?>
									<?=$params[ACCESS_CHANGE.'_icon'];?>
								</td>
								<?php
							}
							?>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function groups_hash() {
		$groups_hash = [];
		foreach(\Groups::permissions_subject_hash() as $subject_id=>$permissionTitle) {
			if (!\Sessions::checkPermission(\Permissions::MANAGE_GROUP_PERMISSIONS, $subject_id, ACCESS_WRITE)) {
				continue;
			}
			$groups_hash[$subject_id] = $permissionTitle;
		}
		return $groups_hash;
	}

	private function users_hash() {
		$users_hash = [];
		foreach(\Users::permissions_subject_hash() as $subject_id=>$permissionTitle) {
			if (!\Sessions::checkPermission(\Permissions::MANAGE_USER_PERMISSIONS, $subject_id, ACCESS_WRITE)) {
				continue;
			}
			$users_hash[$subject_id] = $permissionTitle;
		}
		return $users_hash;
	}

	private function print_modal() {
		?>
		<div class="modal" id="createPermissionModal" tabindex="-1" role="dialog" aria-labelledby="createPermissionModalLabel" aria-modal="true">
			<div class="modal-dialog modal-fullscreen-md-down" role="document">
				<div class="modal-content bg-dark">
					<form action="<?=$this->url;?>" class="needs-validation" method="post" novalidate>
						<div class="modal-header border-secondary">
							<h5 class="modal-title" id="createPermissionModalLabel"><?=\T::Framework_Permission_ModalTitle();?></h5>
							<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?=\T::Framework_Common_Close();?>" title="<?=\T::Framework_Common_Close();?>"></button>
						</div>
						<div class="modal-body" id="createPermissionModalBody">
							<?php
							echo $this->template->html_select('permissionsType', \Permissions::subject_types_hash(), 0, \T::Framework_Permission_ModalType(), true,[
								'with-undefined' => true,
								'data-container' => '#createPermissionModal',
							]);
							echo $this->template->html_select('subject-select', [], '', \T::Framework_Menu_Subject(), true,[
								'with-undefined' => true,
								'global-id' => 'subject-select-div',
								'data-container' => '#createPermissionModal',
							]);
							?>
						</div>
						<div class="modal-footer border-secondary">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?=\T::Framework_Common_Cancel();?></button>
							<button type="submit" class="btn btn-success" id="confirmCreateBtn" name="action" value="create"><?=\T::Framework_Common_Create();?></button>
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

				$('.addPermissionAction').on('click', function() {
					showCreatePermissionModal('', '');
				});

				$('#permissionsType').on('change', function() {
					const selectedValue = $(this).val();

					// $('#subject-select').val('').change();
					// $('#subject-select').selectpicker('destroy');
					$('#subject-select').load(
						'?',
						{
							subjectType: selectedValue,
							ajax:"1",
							method: 'ajaxSubjects'
						},
						function(response, status, xhr) {
							$('#subject-select').val('').change();
							$('#subject-select').selectpicker('destroy').selectpicker();
						}
					);
				});

				const checkboxes = document.querySelectorAll('.access-checkpoint');
				checkboxes.forEach(checkbox => {
					const parent = checkbox.closest('td');
					parent.dataset.order = parseInt(checkbox.dataset.code);

					checkbox.addEventListener('click', () => {
						// Get parent object
						const parent = checkbox.closest('td');
						// Если у родителя есть класс disabled то не давать кликать
						if (parent.classList.contains('disabled')) {
							return;
						}
						const subject = parent.dataset.subject;
						const subject_id = parent.dataset.subject_id;
						const access = parent.dataset.access;
						let accessValue = parseInt(checkbox.dataset.code);
						if (accessValue == 2) {
							accessValue = 0;
						} else {
							accessValue = accessValue + 1;
						}
						// checkbox.setAttribute('data-code', accessValue);

						const formData = new FormData();
						formData.append('action', 'save');
						formData.append('subject', subject);
						formData.append('subject_id', subject_id);
						formData.append('access', access);
						formData.append('value', accessValue);
						<?php if ($this->group_id) { ?>
							formData.append('group_id', <?=$this->group_id?>);
						<?php } elseif ($this->user_id) { ?>
							formData.append('user_id', <?=$this->user_id?>);
						<?php } ?>
						checkbox.classList.add('spinning');
						// Ajax Post query
						fetch('<?=$this->url;?>', {
							method: 'POST',
							body: formData
						})
						.then(response => response.json())
						.then(data => {
							console.log(data);
							if (data.status > 0) {
								checkbox.setAttribute('data-code', data.data_code);
								checkbox.className = data.class;
								checkbox.title = data.title;
								parent.dataset.order = accessValue;
								table = $('#permissionsTable').DataTable();
								// Обновляем ячейку в DataTable
								const cell = table.cell(parent);
								cell.invalidate();
							}
						})
						.catch(error => {
							console.error('Error:', error);
							checkbox.classList.remove('spinning');
						});
					});
				});

				const permissionsTable = new DataTable('#permissionsTable');

			});

			function showCreatePermissionModal(groupId, groupTitle) {
				const modal = new bootstrap.Modal(document.getElementById('createPermissionModal'));
				const modalBody = document.getElementById('createPermissionModalBody');
				const confirmBtn = document.getElementById('confirmCreateBtn');
				modal.show();

				const modalElement = document.getElementById('createPermissionModal');
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