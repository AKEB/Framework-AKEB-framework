<?php
class Framework_Migration_0031 {
	static public $previous = [
		'framework_migration_0018',
		'framework_migration_0027',
		'framework_migration_0030',
	];

	static function install() {
		global $db;
		$db->execSQL("DROP TABLE IF EXISTS `permissions`;");
		$db->execSQL("DELETE FROM `translates` WHERE `table` = 'permissions';");
	}

	static function uninstall() {
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
				(3, '".\Users::PERMISSION_CREATE_USER."'),
				(7, '".\Groups::PERMISSION_CREATE_GROUP."'),
				(10, '".\Permissions::LOGS."')
		");

		$db->execSQL("
			INSERT INTO `translates`
				(`id`, `table`, `field`, `field_id`, `language`, `value`)
			VALUES
				(NULL, 'permissions', 'title', 1, 'ru', 'Admin'),
				(NULL, 'permissions', 'title', 3, 'ru', 'Создание пользователя'),
				(NULL, 'permissions', 'title', 7, 'ru', 'Создание группы'),
				(NULL, 'permissions', 'title', 10, 'ru', 'Просмотр логов'),

				(NULL, 'permissions', 'title', 1, 'en', 'Admin'),
				(NULL, 'permissions', 'title', 3, 'en', 'Create User'),
				(NULL, 'permissions', 'title', 7, 'en', 'Create Group'),
				(NULL, 'permissions', 'title', 10, 'en', 'Logs report')
		");

	}
}

