<?php
class Framework_Migration_0023 {
	static public $previous = [
		'framework_migration_0002',
		'framework_migration_0003',
		'framework_migration_0009',
	];

	static function install() {
		global $db;
		$db->execSQL("
			ALTER TABLE `users`
				CHANGE `creatorUserId` `creator_user_id` int NOT NULL DEFAULT 0,
				CHANGE `registerTime` `register_time` int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `updateTime` `update_time` int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `loginTime` `login_time` int NOT NULL DEFAULT 0,
				CHANGE `loginTryTime` `login_try_time` int NOT NULL DEFAULT 0,
				ADD `email_verification_token` CHAR(64) NOT NULL DEFAULT '' AFTER `email`
			;
		");
		$db->execSQL("
			ALTER TABLE `sessions`
				CHANGE `userId` `user_id` bigint UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `sessionStartTime`  `session_start_time`   int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `sessionExpireTime` `session_expire_time`  int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `sessionJsonData`   `session_json_data`    text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
			;
		");
		$db->execSQL("
			ALTER TABLE `groups`
				CHANGE `createTime` `create_time` int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `updateTime` `update_time` int UNSIGNED NOT NULL DEFAULT 0
			;
		");
		$db->execSQL("
			ALTER TABLE `user_groups`
				CHANGE `createTime` `create_time` int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `updateTime` `update_time` int UNSIGNED NOT NULL DEFAULT 0
			;
		");
		$db->execSQL("
			ALTER TABLE `object_permissions`
				CHANGE `createTime` `create_time` int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `updateTime` `update_time` int UNSIGNED NOT NULL DEFAULT 0
			;
		");
	}

	static function uninstall() {
		global $db;
		$db->execSQL("
			ALTER TABLE `users`
				CHANGE `creator_user_id` `creatorUserId` int NOT NULL DEFAULT 0,
				CHANGE `register_time` `registerTime` int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `update_time` `updateTime` int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `login_time` `loginTime` int NOT NULL DEFAULT 0,
				CHANGE `login_try_time` `loginTryTime` int NOT NULL DEFAULT 0,
				DROP `email_verification_token`
			;
		");
		$db->execSQL("
			ALTER TABLE `sessions`
				CHANGE `user_id` `userId` bigint UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `session_start_time`  `sessionStartTime`  int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `session_expire_time` `sessionExpireTime` int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `session_json_data`   `sessionJsonData`   text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
			;
		");
		$db->execSQL("
			ALTER TABLE `groups`
				CHANGE `create_time` `createTime` int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `update_time` `updateTime` int UNSIGNED NOT NULL DEFAULT 0
			;
		");
		$db->execSQL("
			ALTER TABLE `user_groups`
				CHANGE `create_time` `createTime` int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `update_time` `updateTime` int UNSIGNED NOT NULL DEFAULT 0
			;
		");
		$db->execSQL("
			ALTER TABLE `object_permissions`
				CHANGE `create_time` `createTime` int UNSIGNED NOT NULL DEFAULT 0,
				CHANGE `update_time` `updateTime` int UNSIGNED NOT NULL DEFAULT 0
			;
		");
	}
}

