<?php
class Framework_Migration_0011 {
	static public $previous = [
		'framework_migration_0009',
	];

	static function install() {
		global $db;
		$db->execSQL("
			ALTER TABLE `object_permissions`
				ADD `access_read` TINYINT NOT NULL DEFAULT '0' AFTER `delete`,
				ADD `access_write` TINYINT NOT NULL DEFAULT '0' AFTER `access_read`,
				ADD `access_delete` TINYINT NOT NULL DEFAULT '0' AFTER `access_write`
			;
		");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("
			ALTER TABLE `object_permissions` DROP `access_read`, `access_write`, `access_delete`;
		");
	}
}

