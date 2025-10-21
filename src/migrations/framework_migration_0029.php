<?php
class Framework_Migration_0029 {
	static public $previous = [
		'framework_migration_0016',
	];

	static function install() {
		global $db;
		$db->execSQL("DELETE FROM `translates` WHERE `table` = 'log_actions';");

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
				(NULL, 'log_actions', 'title', 2, 'ru', 'Создание'),
				(NULL, 'log_actions', 'title', 3, 'ru', 'Изменение'),
				(NULL, 'log_actions', 'title', 4, 'ru', 'Удаление'),
				(NULL, 'log_actions', 'title', 5, 'ru', 'Вход'),
				(NULL, 'log_actions', 'title', 6, 'ru', 'Выход'),
				(NULL, 'log_actions', 'title', 7, 'ru', 'Вход под пользователем'),
				(NULL, 'log_actions', 'title', 8, 'ru', 'Выход из под пользователя');
		");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("DELETE FROM `translates` WHERE `table` = 'log_actions';");
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
}

