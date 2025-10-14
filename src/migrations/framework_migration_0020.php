<?php
class Framework_Migration_0020 {
	static public $previous = [
		'framework_migration_0013',
	];

	static function install() {
		global $db;
		$db->execSQL("ALTER TABLE `users` ADD `2fa` CHAR(64) NOT NULL DEFAULT '' AFTER `password`;");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("ALTER TABLE `users` DROP `2fa`;");
	}
}

