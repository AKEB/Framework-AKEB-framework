<?php
class Framework_Migration_0014 {
	static public $previous = [];

	static function install() {
		global $db;
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `logs` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`user_id` int(11) NOT NULL DEFAULT 0,
				`original_user_id` int(11) NOT NULL DEFAULT 0,
				`code` char(32) NOT NULL DEFAULT '',
				`action` tinyint(4) NOT NULL DEFAULT 0,
				`object` char(32) NOT NULL DEFAULT '',
				`object_id` bigint(20) NOT NULL DEFAULT 0,
				`json_data` JSON NOT NULL,
				`comment` text NOT NULL,
				`time` int(11) NOT NULL DEFAULT 0,
				`trace` text NOT NULL,
				PRIMARY KEY (`id`),
				KEY `user_id` (`user_id`),
				KEY `original_user_id` (`original_user_id`),
				KEY `code_action` (`code`, `action`),
				KEY `time` (`time`),
				KEY `object` (`object`,`object_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
		");
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `log_tags` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`log_id` bigint(20) NOT NULL DEFAULT 0,
				`object` char(32) NOT NULL DEFAULT '',
				`object_id` bigint(20) NOT NULL DEFAULT 0,
				`time` int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				KEY `log_id` (`log_id`),
				KEY `time` (`time`),
				KEY `object` (`object`,`object_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
		");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("DROP TABLE IF EXISTS `logs`, `log_tags`;");
	}
}

