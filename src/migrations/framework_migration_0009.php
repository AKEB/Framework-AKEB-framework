<?php
class Framework_Migration_0009 {
	static public $previous = [
		'framework_migration_0004',
		'framework_migration_0007',
		'framework_migration_0008',
	];

	static function install() {
		global $db;
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `object_permissions` (
				`id` bigint NOT NULL AUTO_INCREMENT,
				`object` char(128) NOT NULL,
				`object_id` bigint NOT NULL,
				`permission` char(128) NOT NULL,
				`read` tinyint(1) NOT NULL DEFAULT '0',
				`write` tinyint(1) NOT NULL DEFAULT '0',
				`delete` tinyint(1) NOT NULL DEFAULT '0',
				`createTime` int UNSIGNED NOT NULL DEFAULT '0',
				`updateTime` int UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`),
				UNIQUE KEY `object_permission` (`object`,`object_id`,`permission`) USING BTREE,
				KEY `updateTime` (`updateTime`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
		");
		$db->execSQL("
			INSERT INTO `object_permissions`
				(`object`, `object_id`, `permission`, `read`, `write`, `delete`, `createTime`, `updateTime`)
			SELECT
				'user' as `object`, `user_id` as `object_id`, `permission`, `read`, `write`, `delete`, `createTime`, `updateTime`
			FROM `user_permissions`;
		");
		$db->execSQL("
			INSERT INTO `object_permissions`
				(`object`, `object_id`, `permission`, `read`, `write`, `delete`, `createTime`, `updateTime`)
			SELECT
				'group' as `object`, `group_id` as `object_id`, `permission`, `read`, `write`, `delete`, `createTime`, `updateTime`
			FROM `group_permissions`;
		");
		$db->execSQL("DROP TABLE IF EXISTS `user_permissions`, `group_permissions`;");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `user_permissions` (
				`id` bigint NOT NULL AUTO_INCREMENT,
				`user_id` bigint NOT NULL,
				`permission` char(128) NOT NULL,
				`read` tinyint(1) NOT NULL DEFAULT '0',
				`write` tinyint(1) NOT NULL DEFAULT '0',
				`delete` tinyint(1) NOT NULL DEFAULT '0',
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
				`read` tinyint(1) NOT NULL DEFAULT '0',
				`write` tinyint(1) NOT NULL DEFAULT '0',
				`delete` tinyint(1) NOT NULL DEFAULT '0',
				`createTime` int UNSIGNED NOT NULL DEFAULT '0',
				`updateTime` int UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`),
				UNIQUE KEY `group_permission` (`group_id`,`permission`) USING BTREE,
				KEY `updateTime` (`updateTime`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
		");
		$db->execSQL("
			INSERT INTO `user_permissions`
				(`user_id`, `permission`, `read`, `write`, `delete`, `createTime`, `updateTime`)
			SELECT
				`object_id` as `user_id`, `permission`, `read`, `write`, `delete`, `createTime`, `updateTime`
			FROM `object_permissions` WHERE `object` = 'user';
		");
		$db->execSQL("
			INSERT INTO `group_permissions`
				(`group_id`, `permission`, `read`, `write`, `delete`, `createTime`, `updateTime`)
			SELECT
				`object_id` as `group_id`, `permission`, `read`, `write`, `delete`, `createTime`, `updateTime`
			FROM `object_permissions` WHERE `object` = 'group';
		");
		$db->execSQL("DROP TABLE IF EXISTS `object_permissions`;");
	}
}

