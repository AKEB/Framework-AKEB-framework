<?php
class Framework_Migration_0015 {
	static public $previous = [];

	static function install() {
		global $db;
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `translates` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`table` char(32) NOT NULL DEFAULT '',
				`field` char(32) NOT NULL DEFAULT '',
				`field_id` bigint(20) NOT NULL DEFAULT 0,
				`language` char(2) NOT NULL DEFAULT '',
				`value` text NOT NULL,
				PRIMARY KEY (`id`),
				KEY `table_field_field_id_language` (`table`,`field`,`field_id`,`language`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
		");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("DROP TABLE IF EXISTS `translates`;");
	}
}

