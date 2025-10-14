<?php
class Framework_Migration_0013 {
	static public $previous = [
		'framework_migration_0006',
	];

	static function install() {
		global $db;
		$db->execSQL("ALTER TABLE `users` ADD `telegram_id` BIGINT NOT NULL DEFAULT '0' AFTER `email`;");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("ALTER TABLE `users` DROP `telegram_id`;");
	}
}

