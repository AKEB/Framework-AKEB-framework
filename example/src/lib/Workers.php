<?php

class Workers implements \PermissionSubject_Interface {

	const PERMISSION_WORKER = 'worker';

	static public function subject_hash(): array {
		$data_hash = [];
		foreach(static::permissions_subject_hash() as $subject_id=>$permissionTitle) {
			if (!\Sessions::checkPermission(static::PERMISSION_WORKER, $subject_id, ACCESS_WRITE)) {
				continue;
			}
			$data_hash[$subject_id] = $permissionTitle;
		}
		return $data_hash;
	}

	static public function permissions_subject_hash(): array {
		return [
			1 => 'worker 1',
			2 => 'worker 2',
			3 => 'worker 3',
			4 => 'worker 4',
			5 => 'worker 5',
		];
	}
	static public function permissions_hash(): array {
		return [
			static::PERMISSION_WORKER => 'Monitor worker',
		];
	}

	static public function getUserPermissions(array $user): array {
		$permissions = [];
		foreach(static::permissions_subject_hash() as $subject_id=>$subject_title) {
			foreach(static::permissions_hash() as $subject => $permission_title) {
				$permissions[$subject][$subject_id] = [
					READ => \Sessions::checkPermission($subject, $subject_id, READ, $user) ? 1 : 0,
					WRITE => \Sessions::checkPermission($subject, $subject_id, WRITE, $user) ? 1 : 0,
					DELETE => \Sessions::checkPermission($subject, $subject_id, DELETE, $user) ? 1 : 0,
					ACCESS_READ => \Sessions::checkPermission($subject, $subject_id, ACCESS_READ, $user) ? 1 : 0,
					ACCESS_WRITE => \Sessions::checkPermission($subject, $subject_id, ACCESS_WRITE, $user) ? 1 : 0,
					ACCESS_CHANGE => \Sessions::checkPermission($subject, $subject_id, ACCESS_CHANGE, $user) ? 1 : 0,
				];
			}
		}
		return $permissions;
	}
}