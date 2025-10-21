<?php
class Framework_Migration_0028 {
	static public $previous = [
		'framework_migration_0017',
	];

	static function install() {
		global $db;
		$db->execSQL("UPDATE `logs` SET `object`='Users' WHERE `object` = 'user';");
		$db->execSQL("DROP TABLE IF EXISTS `log_objects`;");
		$db->execSQL("DELETE FROM `translates` WHERE `table` = 'log_objects';");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `log_objects` (
				`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`object` char(32) NOT NULL DEFAULT '',
				PRIMARY KEY (`id`),
				UNIQUE KEY `object` (`object`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
		");

		$db->execSQL("
			INSERT INTO `log_objects`
				(`id`, `object`)
			VALUES
				(1, 'Groups'),
				(2, 'ObjectPermissions'),
				(3, 'Sessions'),
				(4, 'UserGroups'),
				(5, 'Users')
		");
		$db->execSQL("
			INSERT INTO `translates`
				(`id`, `table`, `field`, `field_id`, `language`, `value`)
			VALUES
				(NULL, 'log_objects', 'title', 1, 'ru', 'Группа'),
				(NULL, 'log_objects', 'title', 2, 'ru', 'Права доступа'),
				(NULL, 'log_objects', 'title', 3, 'ru', 'Сессия'),
				(NULL, 'log_objects', 'title', 4, 'ru', 'Группа пользователя'),
				(NULL, 'log_objects', 'title', 5, 'ru', 'Пользователь'),

				(NULL, 'log_objects', 'title', 1, 'en', 'Group'),
				(NULL, 'log_objects', 'title', 2, 'en', 'Object permissions'),
				(NULL, 'log_objects', 'title', 3, 'en', 'Session'),
				(NULL, 'log_objects', 'title', 4, 'en', 'User group'),
				(NULL, 'log_objects', 'title', 5, 'en', 'User')
		");
	}
}

