<?php
class Framework_Migration_0024 {
	static public $previous = [
		'framework_migration_0013',
	];

	static function install() {
		global $db;
		$db->execSQL("ALTER TABLE `users` CHANGE `telegram_id` `telegram_id` VARCHAR(64) NOT NULL DEFAULT '';");
		$db->execSQL("UPDATE `users` SET `telegram_id` = '' WHERE `telegram_id` is NULL or `telegram_id` = 0;");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("ALTER TABLE `users` CHANGE `telegram_id` `telegram_id` BIGINT NOT NULL DEFAULT 0;");
	}
}

