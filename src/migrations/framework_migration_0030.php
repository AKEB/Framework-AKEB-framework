<?php
class Framework_Migration_0030 {
	static public $previous = [
		'framework_migration_0018',
	];

	static function install() {
		global $db;

		$result = [];
		$db->db_GetQueryArray("SELECT `id` FROM `permissions` WHERE `permission` IN (
			'".\Users::PERMISSION_MANAGE_USERS."',
			'".\Users::PERMISSION_MANAGE_USER_PERMISSIONS."',
			'".\Users::PERMISSION_MANAGE_USER_GROUPS."',
			'".\Users::PERMISSION_IMPERSONATE_USER."',
			'".\Groups::PERMISSION_MANAGE_GROUPS."',
			'".\Groups::PERMISSION_MANAGE_GROUP_PERMISSIONS."'
		);", $result);
		$permissions_ids = [];
		foreach($result as $item) {
			$permissions_ids[] = intval($item['id']);
		}

		$db->execSQL("DELETE FROM `translates` WHERE `table` = 'permissions' AND `field_id` IN (".implode(',', $permissions_ids).");");
		$db->execSQL("DELETE FROM `permissions` WHERE `permission` IN (
			'".\Users::PERMISSION_MANAGE_USERS."',
			'".\Users::PERMISSION_MANAGE_USER_PERMISSIONS."',
			'".\Users::PERMISSION_MANAGE_USER_GROUPS."',
			'".\Users::PERMISSION_IMPERSONATE_USER."',
			'".\Groups::PERMISSION_MANAGE_GROUPS."',
			'".\Groups::PERMISSION_MANAGE_GROUP_PERMISSIONS."'
		);");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("
			INSERT INTO `permissions`
				(`id`, `permission`)
			VALUES
				(2, '".\Users::PERMISSION_MANAGE_USERS."'),
				(4, '".\Users::PERMISSION_MANAGE_USER_PERMISSIONS."'),
				(5, '".\Users::PERMISSION_MANAGE_USER_GROUPS."'),
				(6, '".\Groups::PERMISSION_MANAGE_GROUPS."'),
				(8, '".\Groups::PERMISSION_MANAGE_GROUP_PERMISSIONS."'),
				(9, '".\Users::PERMISSION_IMPERSONATE_USER."');
		");

		$db->execSQL("
			INSERT INTO `translates`
				(`id`, `table`, `field`, `field_id`, `language`, `value`)
			VALUES
				(NULL, 'permissions', 'title', 2, 'ru', 'Управление пользователями'),
				(NULL, 'permissions', 'title', 4, 'ru', 'Управление правами пользователя'),
				(NULL, 'permissions', 'title', 5, 'ru', 'Управление группами пользователя'),
				(NULL, 'permissions', 'title', 6, 'ru', 'Управление группами'),
				(NULL, 'permissions', 'title', 8, 'ru', 'Управление правами группы'),
				(NULL, 'permissions', 'title', 9, 'ru', 'Вход под пользователем'),

				(NULL, 'permissions', 'title', 2, 'en', 'Manage Users'),
				(NULL, 'permissions', 'title', 4, 'en', 'Manage User Permissions'),
				(NULL, 'permissions', 'title', 5, 'en', 'Manage User Groups'),
				(NULL, 'permissions', 'title', 6, 'en', 'Manage Groups'),
				(NULL, 'permissions', 'title', 8, 'en', 'Manage Group Permissions'),
				(NULL, 'permissions', 'title', 9, 'en', 'Impersonate Users')
		");
	}
}

