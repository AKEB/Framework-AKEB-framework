<?php

class Workers implements \PermissionSubject_Interface {

	const PERMISSION_WORKER = 'worker';


	static public function permissions_get_type(): string {
		return 'worker';
	}

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

}