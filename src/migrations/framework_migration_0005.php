<?php
class Framework_Migration_0005 {
	static public $previous = [
		'framework_migration_0002',
	];

	static function install() {
		global $db;
		$db->execSQL("ALTER TABLE `users` DROP `role`;");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("ALTER TABLE `users` ADD `role` CHAR(32) NOT NULL DEFAULT 'user' AFTER `status`;");
	}
}

