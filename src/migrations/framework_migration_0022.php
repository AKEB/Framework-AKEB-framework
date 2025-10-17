<?php
class Framework_Migration_0022 {
	static public $previous = [
		'framework_migration_0020',
	];

	static function install() {
		global $db;
		$db->execSQL("ALTER TABLE `users` CHANGE `2fa` `two_factor_secret` CHAR(64) NOT NULL DEFAULT '';");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("ALTER TABLE `users` CHANGE `two_factor_secret` `2fa` CHAR(64) NOT NULL DEFAULT '';");
	}
}

