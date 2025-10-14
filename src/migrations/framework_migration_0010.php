<?php
class Framework_Migration_0010 {
	static public $previous = [
		'framework_migration_0009',
	];

	static function install() {
		global $db;
		$db->execSQL("ALTER TABLE `object_permissions` ADD `subject_id` BIGINT NOT NULL DEFAULT '0' AFTER `permission`;");
		$db->execSQL("ALTER TABLE `object_permissions` CHANGE `permission` `subject` CHAR(128) NOT NULL;");
		$db->execSQL("ALTER TABLE `object_permissions` DROP INDEX `object_permission`, ADD UNIQUE `object_permission` (`object`, `object_id`, `subject`, `subject_id`) USING BTREE;");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("ALTER TABLE `object_permissions` CHANGE `subject` `permission` CHAR(128) NOT NULL;");
		$db->execSQL("ALTER TABLE `object_permissions` DROP INDEX `object_permission`, ADD UNIQUE `object_permission` (`object`, `object_id`, `permission`) USING BTREE;");
		$db->execSQL("ALTER TABLE `object_permissions` DROP `subject_id`;");
	}
}

