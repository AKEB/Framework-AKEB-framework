<?php
class Framework_Migration_0027 {
	static public $previous = [
		'framework_migration_0018',
	];

	static function install() {
		global $db;
		$db->execSQL("
			INSERT INTO `permissions`
				(`id`, `permission`)
			VALUES
				(10, '".\Permissions::LOGS."')
		");

		$db->execSQL("
			INSERT INTO `translates`
				(`id`, `table`, `field`, `field_id`, `language`, `value`)
			VALUES
				(NULL, 'permissions', 'title', 10, 'ru', 'Просмотр логов'),
				(NULL, 'permissions', 'title', 10, 'en', 'Logs report')
		");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("DELETE FROM `translates` WHERE `table` = 'permissions' AND `field_id`=10;");
		$db->execSQL("DELETE FROM `permissions` WHERE `id`=10;");
	}
}

