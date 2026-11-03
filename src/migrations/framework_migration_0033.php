<?php
class Framework_Migration_0033 {
	static public $previous = [
		'framework_migration_0032',
	];

	static function install() {
		global $db;
		// bcrypt/argon2 password_hash() needs more than legacy md5 char(32)
		$db->execSQL("ALTER TABLE `users` CHANGE `password` `password` VARCHAR(255) NOT NULL DEFAULT '';");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("ALTER TABLE `users` CHANGE `password` `password` CHAR(32) NOT NULL DEFAULT '';");
	}
}
