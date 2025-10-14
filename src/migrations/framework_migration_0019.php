<?php
class Framework_Migration_0019 {
	static public $previous = [
		'framework_migration_0015',
	];

	static function install() {
		global $db;
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `permissions_subject_types` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`subject_type` char(32) NOT NULL DEFAULT '',
				`subject_class` char(128) NOT NULL DEFAULT '',
				PRIMARY KEY (`id`),
				UNIQUE KEY `subject_type_subject_class` (`subject_type`,`subject_class`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
		");
		$db->execSQL("
			INSERT INTO `permissions_subject_types`
				(`id`, `subject_type`, `subject_class`)
			VALUES
				(1, 'user', '\\\\Users'),
				(2, 'group', '\\\\Groups')
		");
		$db->execSQL("
			INSERT INTO `translates`
				(`id`, `table`, `field`, `field_id`, `language`, `value`)
			VALUES
				(NULL, 'permissions_subject_types', 'title', 1, 'ru', 'Пользователь'),
				(NULL, 'permissions_subject_types', 'title', 2, 'ru', 'Группа'),
				(NULL, 'permissions_subject_types', 'title', 1, 'en', 'User'),
				(NULL, 'permissions_subject_types', 'title', 2, 'en', 'Group')
		");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("DROP TABLE IF EXISTS `permissions_subject_types`;");
		$db->execSQL("DELETE FROM `translates` WHERE `table` = 'permissions_subject_types';");
	}
}

