<?php
class Framework_Migration_0007 {
	static public $previous = [
		'framework_migration_0004',
	];

	static function install() {
		global $db;
		$db->execSQL("ALTER TABLE `group_permissions` CHANGE `read` `read` TINYINT(1) NOT NULL DEFAULT '0';");
		$db->execSQL("ALTER TABLE `group_permissions` CHANGE `write` `write` TINYINT(1) NOT NULL DEFAULT '0';");
		$db->execSQL("ALTER TABLE `group_permissions` CHANGE `delete` `delete` TINYINT(1) NOT NULL DEFAULT '0';");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("ALTER TABLE `group_permissions` CHANGE `read` `read` TINYINT(1) NOT NULL;");
		$db->execSQL("ALTER TABLE `group_permissions` CHANGE `write` `write` TINYINT(1) NOT NULL;");
		$db->execSQL("ALTER TABLE `group_permissions` CHANGE `delete` `delete` TINYINT(1) NOT NULL;");
	}
}

