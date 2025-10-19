<?php

class Users extends \DB\MySQLObject implements \PermissionSubject_Interface {
	static public $table = 'users';

	const LOGS_OBJECT = 'Users';

	const STATUS_ACTIVE               =  1;
	const STATUS_INACTIVE             =  2;
	const STATUS_EMAIL_VERIFICATION   =  3;

	/** Требовать сменить пароль */
	const FLAGS_NEED_CHANGE_PASSWORD = 1 << 0; // 1
	// const FLAGS_SECOND               = 1 << 1; // 2
	// const FLAGS_SECOND2              = 1 << 2; // 4

	/**
	 * Флаги
	 *
	 * @return array массив флагов
	 */
	static public function flags_hash() {
		return [
			self::FLAGS_NEED_CHANGE_PASSWORD => \T::Framework_Users_Flags_NeedChangePassword(),
			// self::FLAGS_SECOND => \T::Framework_Users_Flags_Second(),
			// self::FLAGS_SECOND2 => \T::Framework_Users_Flags_Second2(),
		];
	}

	static public function password_hash(string $password): string {
		if (!$password) return '';
		return md5($password . \Config::getInstance()->password_salt);
	}

	static public function password_verify(string $password, string $password_hash): bool {
		if (!$password || !$password_hash) return false;
		return $password_hash == static::password_hash($password);
	}

	static public function generate_verification_token(): string {
		return bin2hex(random_bytes(32));
	}

	static public function generate_reset_token(): string {
		return bin2hex(random_bytes(32));
	}

	static public function check_user_credentials(string $email, string $password):int|bool {
		if (!$email || !$password) return false;
		$user = static::get([
			'email' => $email,
			'status' => self::STATUS_ACTIVE,
		]);
		if (!$user) return false;
		if ($user['login_try_time'] && (time() - $user['login_try_time']) < 2) {
			return -1;
		}
		$user['login_try_time'] = time();
		static::save([
			'id' => $user['id'],
			'login_try_time' => $user['login_try_time'],
			'_mode' => \DB\Common::CSMODE_UPDATE,
		]);
		if (!$user['password']) return false;
		if (!static::password_verify($password, $user['password'])) return false;
		return $user['id'];
	}

	static public function permissions_get_type(): string {
		return 'user';
	}

	static public function subject_hash(): array {
		$data_hash = [];
		foreach(static::permissions_subject_hash() as $subject_id=>$permissionTitle) {
			if (!\Sessions::checkPermission(\Permissions::MANAGE_USER_PERMISSIONS, $subject_id, ACCESS_WRITE)) {
				continue;
			}
			$data_hash[$subject_id] = $permissionTitle;
		}
		return $data_hash;
	}

	static public function permissions_subject_hash(): array {
		$data = static::data();
		$permissions_hash = [];
		foreach($data as $item) {
			$permissions_hash[$item['id']] = $item['name'].' '.$item['surname'].' ('.$item['email'].')';
		}
		return $permissions_hash;
	}

	static public function permissions_hash(): array {
		return [
			\Permissions::MANAGE_USERS => \T::Framework_Permissions_ManageUser(),
			\Permissions::MANAGE_USER_PERMISSIONS => \T::Framework_Permissions_ManageUserPermissions(),
			\Permissions::MANAGE_USER_GROUPS => \T::Framework_Permissions_ManageUserGroups(),
			\Permissions::IMPERSONATE_USER => \T::Framework_Permissions_ImpersonateUser(),
		];
	}

	static public function clear_session_cache($user_id): void {
		$sessions = \Sessions::data(['user_id' => $user_id]);
		foreach($sessions as $session) {
			$cache = new \Cache('session_init_'.md5($session['id']));
			$cache->remove();
		}
	}

	static public function save($param, $table_fields='', $ref_name='id', $add=''): int|bool {
		if ($param['id']??false) {
			static::clear_session_cache($param['id']);
		}
		return parent::save($param, $table_fields, $ref_name, $add);
	}
}