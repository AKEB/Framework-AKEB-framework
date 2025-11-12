<?php
class Framework_Migration_0032 {
	static public $previous = [
		'framework_migration_0025',
	];

	static function install() {
		global $db;
		$db->execSQL("ALTER TABLE `notifications` ADD `success` TINYINT(1) NOT NULL DEFAULT 1 AFTER `body`;");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("ALTER TABLE `notifications` DROP `success`;");
	}
}

