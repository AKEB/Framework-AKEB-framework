<?php
class Framework_Migration_0016 {
	static public $previous = [
		'framework_migration_0015',
	];

	static function install() {
		global $db;
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `log_actions` (
				`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
		");

		$db->execSQL("
			INSERT INTO `log_actions`
				(`id`)
			VALUES
				(1),
				(2),
				(3),
				(4),
				(5),
				(6),
				(7),
				(8)
		");
		$db->execSQL("
			INSERT INTO `translates`
				(`id`, `table`, `field`, `field_id`, `language`, `value`)
			VALUES
				(NULL, 'log_actions', 'title', 1, 'en', 'Other'),
				(NULL, 'log_actions', 'title', 2, 'en', 'Create'),
				(NULL, 'log_actions', 'title', 3, 'en', 'Update'),
				(NULL, 'log_actions', 'title', 4, 'en', 'Delete'),
				(NULL, 'log_actions', 'title', 5, 'en', 'Login'),
				(NULL, 'log_actions', 'title', 6, 'en', 'Logout'),
				(NULL, 'log_actions', 'title', 7, 'en', 'Start impersonate user'),
				(NULL, 'log_actions', 'title', 8, 'en', 'Stop impersonate user'),
				(NULL, 'log_actions', 'title', 1, 'ru', 'Другое'),
				(NULL, 'log_actions', 'title', 2, 'ru', 'Создать'),
				(NULL, 'log_actions', 'title', 3, 'ru', 'Изменить'),
				(NULL, 'log_actions', 'title', 4, 'ru', 'Удалить'),
				(NULL, 'log_actions', 'title', 5, 'ru', 'Войти'),
				(NULL, 'log_actions', 'title', 6, 'ru', 'Выйти'),
				(NULL, 'log_actions', 'title', 7, 'ru', 'Вход под пользователем'),
				(NULL, 'log_actions', 'title', 8, 'ru', 'Выход из под пользователя');
		");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("DROP TABLE IF EXISTS `log_actions`;");
		$db->execSQL("DELETE FROM `translates` WHERE `table` = 'log_actions';");
	}
}

