<?php
class Framework_Migration_0002 {
	static public $previous = [
		// 'framework_migration_0000',
	];

	static function install() {
		global $db;
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `sessions` (
				`id` char(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
				`userId` bigint UNSIGNED NOT NULL DEFAULT '0',
				`sessionStartTime` int UNSIGNED NOT NULL DEFAULT '0',
				`sessionExpireTime` int UNSIGNED NOT NULL DEFAULT '0',
				`sessionJsonData` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
				PRIMARY KEY (`id`),
				KEY `sessionExpireTime` (`sessionExpireTime`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
		");
		$db->execSQL("
			CREATE TABLE IF NOT EXISTS `users` (
				`id` bigint NOT NULL AUTO_INCREMENT,
				`name` char(64) NOT NULL,
				`surname` char(64) NOT NULL,
				`email` char(128) NOT NULL,
				`language` char(2) NOT NULL DEFAULT 'en',
				`password` char(32) NOT NULL DEFAULT '',
				`status` tinyint NOT NULL DEFAULT '1',
				`role` char(32) NOT NULL DEFAULT 'user',
				`creatorUserId` int NOT NULL DEFAULT 0,
				`registerTime` int UNSIGNED NOT NULL DEFAULT '0',
				`updateTime` int UNSIGNED NOT NULL DEFAULT '0',
				`loginTime` int NOT NULL DEFAULT 0,
				`loginTryTime` int NOT NULL DEFAULT 0,
				`flags` int NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				UNIQUE KEY `email` (`email`,`status`) USING BTREE,
				KEY `updateTime` (`updateTime`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
		");
		$db->execSQL("INSERT INTO `users` (`id`, `name`, `surname`, `email`, `language`, `password`, `role`, `registerTime`, `flags`) VALUES (1, 'admin', 'admin', 'admin@admin.com', 'en', '".md5('Admin@123'.\Config::getInstance()->password_salt)."', 'admin', ".time().", ".(\Users::FLAGS_NEED_CHANGE_PASSWORD).");");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("DROP TABLE IF EXISTS `sessions`, `users`;");
	}
}

