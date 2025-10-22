<?php
class Framework_Migration_0030 {
	static public $previous = [
		'framework_migration_0018',
	];

	static function install() {
		global $db;
		$db->execSQL("DELETE FROM `permissions` WHERE `permission` IN (
			'".\Permissions::MANAGE_USERS."',
			'".\Permissions::MANAGE_USER_PERMISSIONS."',
			'".\Permissions::MANAGE_USER_GROUPS."',
			'".\Permissions::IMPERSONATE_USER."',
			'".\Permissions::MANAGE_GROUPS."',
			'".\Permissions::MANAGE_GROUP_PERMISSIONS."'
		);");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("
			INSERT INTO `permissions`
				(`id`, `permission`)
			VALUES
				(NULL, '".\Permissions::MANAGE_USERS."'),
				(NULL, '".\Permissions::MANAGE_USER_PERMISSIONS."'),
				(NULL, '".\Permissions::MANAGE_USER_GROUPS."'),
				(NULL, '".\Permissions::MANAGE_GROUPS."'),
				(NULL, '".\Permissions::MANAGE_GROUP_PERMISSIONS."'),
				(NULL, '".\Permissions::IMPERSONATE_USER."')
		");
	}
}

