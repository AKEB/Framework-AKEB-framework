<?php
class Framework_Migration_0021 {
	static public $previous = [
		'framework_migration_0002',
	];

	static function install() {
		global $db;
		$db->execSQL("
			ALTER TABLE `users`
				ADD `reset_token` CHAR(64) NOT NULL DEFAULT '' AFTER `password`,
				ADD `reset_token_expires` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `reset_token`;
		");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("ALTER TABLE `users` DROP `reset_token`, DROP `reset_token_expires`;");
	}
}

