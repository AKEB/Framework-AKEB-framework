<?php
class Framework_Migration_0001 {
	static public $previous = [
		'framework_migration_0000',
	];

	static function install() {
		global $db;
		$db->execSQL("DROP TABLE IF EXISTS `test_table`");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("CREATE TABLE IF NOT EXISTS `test_table` LIKE `migrations`");
	}
}

