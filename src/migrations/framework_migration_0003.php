<?php
class Framework_Migration_0003 {
	static public $previous = [
		'framework_migration_0002',
	];

	static function install() {
		global $db;
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `groups` (
				`id` bigint NOT NULL AUTO_INCREMENT,
				`title` char(64) NOT NULL,
				`createTime` int UNSIGNED NOT NULL DEFAULT '0',
				`updateTime` int UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`),
				KEY `updateTime` (`updateTime`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
		");
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `user_groups` (
				`id` bigint NOT NULL AUTO_INCREMENT,
				`user_id` bigint NOT NULL,
				`group_id` bigint NOT NULL,
				`createTime` int UNSIGNED NOT NULL DEFAULT '0',
				`updateTime` int UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`),
				UNIQUE KEY `user_group` (`user_id`,`group_id`) USING BTREE,
				KEY `updateTime` (`updateTime`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
		");
		$db->execSQL("INSERT INTO `groups` (`id`, `title`, `createTime`, `updateTime`) VALUES (1, 'Admin', ".time().", ".time().");");
		$db->execSQL("INSERT INTO `groups` (`id`, `title`, `createTime`, `updateTime`) VALUES (2, 'Default', ".time().", ".time().");");
		$db->execSQL("INSERT INTO `user_groups` (`id`, `user_id`, `group_id`, `createTime`, `updateTime`) VALUES (1,1,1,".time().",".time().");");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("DROP TABLE IF EXISTS `groups`, `user_groups`;");
	}
}

