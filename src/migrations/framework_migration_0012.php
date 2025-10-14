<?php
class Framework_Migration_0012 {
	static public $previous = [
		'framework_migration_0011',
	];

	static function install() {
		global $db;
		$db->execSQL("ALTER TABLE `object_permissions` CHANGE `access_delete` `access_change` TINYINT NOT NULL DEFAULT '0';");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("
			ALTER TABLE `object_permissions` CHANGE `access_change` `access_delete` TINYINT NOT NULL DEFAULT '0';
		");
	}
}

