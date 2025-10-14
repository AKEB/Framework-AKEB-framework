<?php
class Framework_Migration_0004 {
	static public $previous = [
		'framework_migration_0002',
		'framework_migration_0003',
	];

	static function install() {
		global $db;
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `user_permissions` (
				`id` bigint NOT NULL AUTO_INCREMENT,
				`user_id` bigint NOT NULL,
				`permission` char(128) NOT NULL,
				`read` tinyint(1) NOT NULL,
				`write` tinyint(1) NOT NULL,
				`delete` tinyint(1) NOT NULL,
				`createTime` int UNSIGNED NOT NULL DEFAULT '0',
				`updateTime` int UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`),
				UNIQUE KEY `user_permission` (`user_id`,`permission`) USING BTREE,
				KEY `updateTime` (`updateTime`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
		");

		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `group_permissions` (
				`id` bigint NOT NULL AUTO_INCREMENT,
				`group_id` bigint NOT NULL,
				`permission` char(128) NOT NULL,
				`read` tinyint(1) NOT NULL,
				`write` tinyint(1) NOT NULL,
				`delete` tinyint(1) NOT NULL,
				`createTime` int UNSIGNED NOT NULL DEFAULT '0',
				`updateTime` int UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`),
				UNIQUE KEY `group_permission` (`group_id`,`permission`) USING BTREE,
				KEY `updateTime` (`updateTime`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
		");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("DROP TABLE IF EXISTS `user_permissions`, `group_permissions`;");
	}
}

