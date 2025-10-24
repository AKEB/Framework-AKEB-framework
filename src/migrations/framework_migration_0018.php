<?php
class Framework_Migration_0018 {
	static public $previous = [
		'framework_migration_0015',
	];

	static function install() {
		global $db;
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `permissions` (
				`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`permission` char(32) NOT NULL DEFAULT '',
				PRIMARY KEY (`id`),
				UNIQUE KEY `permission` (`permission`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
		");

		$db->execSQL("
			INSERT INTO `permissions`
				(`id`, `permission`)
			VALUES
				(1, '".\Permissions::ADMIN."'),
				(2, '".\Users::PERMISSION_MANAGE_USERS."'),
				(3, '".\Users::PERMISSION_CREATE_USER."'),
				(4, '".\Users::PERMISSION_MANAGE_USER_PERMISSIONS."'),
				(5, '".\Users::PERMISSION_MANAGE_USER_GROUPS."'),
				(6, '".\Groups::PERMISSION_MANAGE_GROUPS."'),
				(7, '".\Groups::PERMISSION_CREATE_GROUP."'),
				(8, '".\Groups::PERMISSION_MANAGE_GROUP_PERMISSIONS."'),
				(9, '".\Users::PERMISSION_IMPERSONATE_USER."')
		");

		$db->execSQL("
			INSERT INTO `translates`
				(`id`, `table`, `field`, `field_id`, `language`, `value`)
			VALUES
				(NULL, 'permissions', 'title', 1, 'ru', 'Admin'),
				(NULL, 'permissions', 'title', 2, 'ru', 'Управление пользователями'),
				(NULL, 'permissions', 'title', 3, 'ru', 'Создание пользователя'),
				(NULL, 'permissions', 'title', 4, 'ru', 'Управление правами пользователя'),
				(NULL, 'permissions', 'title', 5, 'ru', 'Управление группами пользователя'),
				(NULL, 'permissions', 'title', 6, 'ru', 'Управление группами'),
				(NULL, 'permissions', 'title', 7, 'ru', 'Создание группы'),
				(NULL, 'permissions', 'title', 8, 'ru', 'Управление правами группы'),
				(NULL, 'permissions', 'title', 9, 'ru', 'Вход под пользователем'),

				(NULL, 'permissions', 'title', 1, 'en', 'Admin'),
				(NULL, 'permissions', 'title', 2, 'en', 'Manage Users'),
				(NULL, 'permissions', 'title', 3, 'en', 'Create User'),
				(NULL, 'permissions', 'title', 4, 'en', 'Manage User Permissions'),
				(NULL, 'permissions', 'title', 5, 'en', 'Manage User Groups'),
				(NULL, 'permissions', 'title', 6, 'en', 'Manage Groups'),
				(NULL, 'permissions', 'title', 7, 'en', 'Create Group'),
				(NULL, 'permissions', 'title', 8, 'en', 'Manage Group Permissions'),
				(NULL, 'permissions', 'title', 9, 'en', 'Impersonate Users')
		");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("DROP TABLE IF EXISTS `permissions`;");
		$db->execSQL("DELETE FROM `translates` WHERE `table` = 'permissions';");
	}
}

