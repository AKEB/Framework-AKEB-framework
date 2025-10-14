<?php
class Framework_Migration_0006 {
	static public $previous = [
		'framework_migration_0002',
	];

	static function install() {
		global $db;
		$db->execSQL("ALTER TABLE `users` DROP `language`;");
		$db->execSQL("ALTER TABLE `users` ADD `cookie` JSON AFTER `flags`;");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("ALTER TABLE `users` ADD `language` CHAR(2) NOT NULL DEFAULT 'en' AFTER `email`;");
		$db->execSQL("ALTER TABLE `users` DROP `cookie`;");
	}
}

