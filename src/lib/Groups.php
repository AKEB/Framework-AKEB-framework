<?php

class Groups extends \DB\MySQLObject implements \PermissionSubject_Interface {
	static public $table = 'groups';

	const LOGS_OBJECT = 'Groups';

	const ADMIN_GROUP_ID = 1;

	const DEFAULT_GROUP_ID = 2;

	static public function subject_hash(): array {
		$data_hash = [];
		foreach(static::permissions_subject_hash() as $subject_id=>$permissionTitle) {
			if (!\Sessions::checkPermission(\Permissions::MANAGE_GROUP_PERMISSIONS, $subject_id, ACCESS_WRITE)) {
				continue;
			}
			$data_hash[$subject_id] = $permissionTitle;
		}
		return $data_hash;
	}

	static public function permissions_subject_hash(): array {
		$data = static::data();
		$permissions_hash = [];
		foreach($data as $item) {
			$permissions_hash[$item['id']] = $item['title'];
		}
		return $permissions_hash;
	}
	static public function permissions_hash(): array {
		return [
			\Permissions::MANAGE_GROUPS => \T::Framework_Permissions_ManageGroup(),
			\Permissions::MANAGE_GROUP_PERMISSIONS => \T::Framework_Permissions_ManageGroupPermissions(),
		];
	}


}