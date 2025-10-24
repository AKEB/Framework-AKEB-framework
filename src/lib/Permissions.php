<?php

class Permissions {
	const ADMIN = 'admin';
	const LOGS = 'logs';

	private static array $permissions = [];
	private static array $permission_subject_types = [];

	static public function set_subject_type(string $subject_type, string $subject_class, string $title): bool {
		$item = [
			'subject_type' => $subject_type,
			'subject_class' => $subject_class,
			'title' => $title,
		];
		if (!$item['subject_type']) return false;
		if (!$item['subject_class']) return false;
		if (!$item['title']) return false;
		if (!class_exists($item['subject_class'])) return false;
		if (!in_array('PermissionSubject_Interface', class_implements($item['subject_class'], true))) return false;

		static::$permission_subject_types[] = $item;
		return true;
	}

	static public function subject_types_hash(): array {
		if (!static::$permission_subject_types) return [];
		return get_hash(static::$permission_subject_types, 'subject_type', 'title');
	}

	static public function get_subject_classes(): array {
		$data = static::$permission_subject_types;
		if (!$data) return [];
		$subject_types_hash = [];
		foreach($data as $item) {
			if (!isset($item['subject_class'])) continue;
			if (!class_exists($item['subject_class'])) continue;
			if (!in_array('PermissionSubject_Interface', class_implements($item['subject_class'], true))) continue;
			$subject_types_hash[$item['subject_type']] = $item['subject_class'];
		}
		return $subject_types_hash;
	}

	static public function permissions_subject_hash(): array {
		return [];
	}

	static public function add_permission(string $permission, string $title): void {
		static::$permissions[$permission] = $title;
	}

	static public function permissions_hash(): array {
		return static::$permissions;
	}

}