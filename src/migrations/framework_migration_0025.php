<?php
class Framework_Migration_0025 {
	static public $previous = [];

	static function install() {
		global $db;
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `notifications` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`user_id` int(11) NOT NULL DEFAULT 0,
				`title` varchar(255) NOT NULL DEFAULT '',
				`body` text NOT NULL,
				`create_time` int(11) NOT NULL DEFAULT 0,
				`send_time` int(11) NOT NULL DEFAULT 0,
				`read_time` int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				KEY `user_id` (`user_id`),
				KEY `time` (`create_time`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
		");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("DROP TABLE IF EXISTS `notifications`;");
	}
}

