<?php

class Sessions extends \DB\MySQLObject{
	static public $table = 'sessions';

	const LOGS_OBJECT = 'Sessions';

	private static string $session_name = 'session_uid';
	private static string $sessionId = '';

	private static int $originalUserId = 0;
	private static int $currentUserId = 0;

	private static array $current_user = [];

	private static int $sessionLifeTime = 7*86400;

	public static function currentUser(): array {
		return static::$current_user;
	}

	public static function currentUserId(): int {
		return static::$currentUserId??0;
	}

	public static function originalUserId(): int {
		return static::$originalUserId??0;
	}


	public static function set_server_cookie(string $name, $value): bool {
		if (!static::currentUser()) return false;
		static::$current_user['cookie'][$name] = $value;
		return boolval(\Users::save([
			'id' => static::$current_user['id'],
			'cookie' => json_encode(static::$current_user['cookie']),
			'updateTime' => time(),
			'_mode' => \DB\Common::CSMODE_UPDATE,
		]));
	}

	public static function get_server_cookie(string $name, $default_value=null) {
		if (!static::currentUser()) return $default_value;
		if (isset(static::$current_user['cookie'][$name])) {
			return static::$current_user['cookie'][$name];
		}
		return $default_value;
	}


	static public function client_ip() {
		if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
			$ips = explode(',',$_SERVER['HTTP_X_REAL_IP']);
			$ip = trim(end($ips));
		} elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ips = explode(',',$_SERVER['HTTP_CLIENT_IP']);
			$ip = trim(end($ips));
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ips = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
			$ip = trim(end($ips));
		} else {
			$ip=$_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	static public function set_current_user(int $userId, array $jsonData=[]) :void {
		static::new_session_id();
		$session = [
			'id' => static::$sessionId,
			'userId' => $userId ?? 0,
			'sessionStartTime' => time(),
			'sessionExpireTime' => time() + static::$sessionLifeTime,
			'sessionJsonData' => json_encode($jsonData),
			'_mode' => \DB\Common::CSMODE_REPLACE,
		];
		static::save($session);
	}

	static private function new_session_id(): string {
		setcookie(static::$session_name, '', time() + 1, '/');
		$_COOKIE[static::$session_name] = '';
		static::$sessionId = hash('sha512', md5(
			static::client_ip() .
			($_SERVER['HTTP_USER_AGENT'] ?? '') .
			microtime() .
			random_int(100000000,999999999)
		));
		setcookie(static::$session_name, static::$sessionId, time() + static::$sessionLifeTime, '/');
		$_COOKIE[static::$session_name] = static::$sessionId;
		return static::$sessionId;
	}

	static private function get_session_id(): string {
		if (
			!isset($_COOKIE[static::$session_name]) ||
			!$_COOKIE[static::$session_name] ||
			strlen($_COOKIE[static::$session_name]) < 128
		) {
			static::new_session_id();
		}
		static::$sessionId = $_COOKIE[static::$session_name] ?? '';
		return static::$sessionId;
	}

	static public function clear_session(): void {
		if (static::$sessionId) {
			static::delete(['id' => static::$sessionId]);
		}
		setcookie(static::$session_name, '', time() + 1, '/');
		$_COOKIE[static::$session_name] = '';
		static::$sessionId = '';
		static::$current_user = [];
	}

	static public function is_impersonate_user(): bool {
		if (!static::$current_user) {
			return false;
		}
		if (!static::$sessionId) {
			return false;
		}
		$session = static::get(['id' => static::$sessionId]);
		if (!$session) return false;
		$jsonData = [];
		if ($session['sessionJsonData'] && is_string($session['sessionJsonData'])) {
			$jsonData = json_decode($session['sessionJsonData'], true);
			if (!is_array($jsonData)) $jsonData = [];
		}
		return isset($jsonData['impersonateUserId']) && $jsonData['impersonateUserId'];
	}

	static public function start_impersonate_user(int $impersonateUserId): bool {
		if (!isset($impersonateUserId) || !$impersonateUserId) {
			return false;
		}
		if (!static::$current_user || !static::checkPermission(\Permissions::IMPERSONATE_USER, $impersonateUserId, READ)) {
			return false;
		}
		if (!static::$sessionId) {
			return false;
		}
		$session = static::get(['id' => static::$sessionId]);
		if (!$session) return false;
		$jsonData = [];
		if ($session['sessionJsonData'] && is_string($session['sessionJsonData'])) {
			$jsonData = json_decode($session['sessionJsonData'], true);
			if (!is_array($jsonData)) $jsonData = [];
		}
		$jsonData['impersonateUserId'] = $impersonateUserId;
		$sessionUpdate = [
			'id' => $session['id'],
			'sessionJsonData' => json_encode($jsonData),
			'_mode' => \DB\Common::CSMODE_UPDATE,
		];
		static::save($sessionUpdate);
		static::$currentUserId = $impersonateUserId;
		return true;
	}

	static public function stop_impersonate_user(): bool {
		if (!static::$current_user) {
			return false;
		}
		if (!static::$sessionId) {
			return false;
		}
		$session = static::get(['id' => static::$sessionId]);
		if (!$session) return false;
		$jsonData = [];
		if ($session['sessionJsonData'] && is_string($session['sessionJsonData'])) {
			$jsonData = json_decode($session['sessionJsonData'], true);
			if (!is_array($jsonData)) $jsonData = [];
		}
		if (isset($jsonData['impersonateUserId'])) {
			unset($jsonData['impersonateUserId']);
			$sessionUpdate = [
				'id' => $session['id'],
				'sessionJsonData' => json_encode($jsonData),
				'_mode' => \DB\Common::CSMODE_UPDATE,
			];
			static::save($sessionUpdate);
			static::$currentUserId = static::$originalUserId;
		}
		return true;
	}

	static public function getUserGroups(int $userId): array {
		$groups = [];
		$userGroups = \UserGroups::data(['user_id' => $userId]);
		$userGroupsIds = get_hash($userGroups, 'group_id', 'group_id');
		$groups_hash = $userGroupsIds ? make_hash(\Groups::data(['id' => array_keys($userGroupsIds)]),'id') : [];
		if ($userGroups && is_array($userGroups)) {
			foreach($userGroups as $userGroup) {
				$group = $groups_hash[$userGroup['group_id']] ?? [];
				if ($group && is_array($group) && isset($group['title'])) {
					$groups[$group['id']] = $group['title'];
				}
			}
		}
		return $groups;
	}

	static public function getGroupsPermissions(array $groupIds): array {
		$permissions = [];
		if (!$groupIds) return $permissions;
		$groupPermissions = \ObjectPermissions::data(['object'=>'group','object_id' => $groupIds]);
		if ($groupPermissions && is_array($groupPermissions)) {
			foreach($groupPermissions as $groupPermission) {
				if (!isset($groupPermission['subject']) || !$groupPermission['subject']) continue;
				if (!isset($permissions[$groupPermission['subject']])) $permissions[$groupPermission['subject']] = [];
				$permissions[$groupPermission['subject']][$groupPermission['subject_id']] = [
					READ => max($permissions[$groupPermission['subject']][$groupPermission['subject_id']][READ] ?? 0, intval($groupPermission[READ])),
					WRITE => max($permissions[$groupPermission['subject']][$groupPermission['subject_id']][WRITE] ?? 0, intval($groupPermission[WRITE])),
					DELETE => max($permissions[$groupPermission['subject']][$groupPermission['subject_id']][DELETE] ?? 0, intval($groupPermission[DELETE])),
					ACCESS_READ => max($permissions[$groupPermission['subject']][$groupPermission['subject_id']][ACCESS_READ] ?? 0, intval($groupPermission[ACCESS_READ])),
					ACCESS_WRITE => max($permissions[$groupPermission['subject']][$groupPermission['subject_id']][ACCESS_WRITE] ?? 0, intval($groupPermission[ACCESS_WRITE])),
					ACCESS_CHANGE => max($permissions[$groupPermission['subject']][$groupPermission['subject_id']][ACCESS_CHANGE] ?? 0, intval($groupPermission[ACCESS_CHANGE])),
				];
			}
		}
		return $permissions;
	}

	static private function getUserPermissions(array $userId): array {
		$permissions = [];
		if (!$userId) return $permissions;
		$userPermissions = \ObjectPermissions::data(['object'=>'user','object_id' => $userId]);
		if ($userPermissions && is_array($userPermissions)) {
			foreach($userPermissions as $userPermission) {
				if (!isset($userPermission['subject']) || !$userPermission['subject']) continue;
				$permissionName = strval($userPermission['subject']??'') . '_'. intval($userPermission['subject_id']??0);
				if (!isset($permissions[$userPermission['subject']])) $permissions[$userPermission['subject']] = [];
				$permissions[$userPermission['subject']][$userPermission['subject_id']] = [
					READ => max($permissions[$userPermission['subject']][$userPermission['subject_id']][READ] ?? 0, intval($userPermission[READ])),
					WRITE => max($permissions[$userPermission['subject']][$userPermission['subject_id']][WRITE] ?? 0, intval($userPermission[WRITE])),
					DELETE => max($permissions[$userPermission['subject']][$userPermission['subject_id']][DELETE] ?? 0, intval($userPermission[DELETE])),
					ACCESS_READ => max($permissions[$userPermission['subject']][$userPermission['subject_id']][ACCESS_READ] ?? 0, intval($userPermission[ACCESS_READ])),
					ACCESS_WRITE => max($permissions[$userPermission['subject']][$userPermission['subject_id']][ACCESS_WRITE] ?? 0, intval($userPermission[ACCESS_WRITE])),
					ACCESS_CHANGE => max($permissions[$userPermission['subject']][$userPermission['subject_id']][ACCESS_CHANGE] ?? 0, intval($userPermission[ACCESS_CHANGE])),
				];
			}
		}
		return $permissions;
	}

	static public function in_group(int $groupId, int $userId=0) {
		if (!$userId) {
			$user = static::currentUser();
			if (!$user || !isset($user['groups']) || !$user['groups'] || !is_array($user['groups'])) return false;
			if (isset($user['groups'][$groupId])) {
				return true;
			}
		} else {
			$userGroup = \UserGroups::get(['user_id' => $userId, 'group_id' => $groupId]);
			if ($userGroup) {
				return true;
			}
		}
		return false;
	}

	static private function getPermissionsForUser(array &$user): void {
		$user['groups'] = static::getUserGroups($user['id']);
		$groupIds = [];
		if (isset($user['groups']) && is_array($user['groups']) && $user['groups']) {
			$groupIds = array_keys($user['groups']);
		}
		$groupPermissions = static::getGroupsPermissions($groupIds);
		$userPermissions = static::getUserPermissions([$user['id']]);
		$user['permissions'] = [];
		foreach($groupPermissions as $permissionSubject => $permissions) {
			if (!isset($user['permissions'][$permissionSubject])) {
				$user['permissions'][$permissionSubject] = [];
			}
			foreach($permissions as $permissionSubjectId => $permission) {
				if (!isset($user['permissions'][$permissionSubject][$permissionSubjectId])) {
					$user['permissions'][$permissionSubject][$permissionSubjectId] = [
						READ => 0,
						WRITE => 0,
						DELETE => 0,
						ACCESS_READ => 0,
						ACCESS_WRITE => 0,
						ACCESS_CHANGE => 0,
					];
				}
				$user['permissions'][$permissionSubject][$permissionSubjectId] = [
					READ => max($user['permissions'][$permissionSubject][$permissionSubjectId][READ] ?? 0, $permission[READ]),
					WRITE => max($user['permissions'][$permissionSubject][$permissionSubjectId][WRITE] ?? 0, $permission[WRITE]),
					DELETE => max($user['permissions'][$permissionSubject][$permissionSubjectId][DELETE] ?? 0, $permission[DELETE]),
					ACCESS_READ => max($user['permissions'][$permissionSubject][$permissionSubjectId][ACCESS_READ] ?? 0, $permission[ACCESS_READ]),
					ACCESS_WRITE => max($user['permissions'][$permissionSubject][$permissionSubjectId][ACCESS_WRITE] ?? 0, $permission[ACCESS_WRITE]),
					ACCESS_CHANGE => max($user['permissions'][$permissionSubject][$permissionSubjectId][ACCESS_CHANGE] ?? 0, $permission[ACCESS_CHANGE]),
				];
			}
		}
		foreach($userPermissions as $permissionSubject => $permissions) {
			if (!isset($user['permissions'][$permissionSubject])) {
				$user['permissions'][$permissionSubject] = [];
			}
			foreach($permissions as $permissionSubjectId => $permission) {
				if (!isset($user['permissions'][$permissionSubject][$permissionSubjectId])) {
					$user['permissions'][$permissionSubject][$permissionSubjectId] = [
						READ => 0,
						WRITE => 0,
						DELETE => 0,
						ACCESS_READ => 0,
						ACCESS_WRITE => 0,
						ACCESS_CHANGE => 0,
					];
				}
				$user['permissions'][$permissionSubject][$permissionSubjectId] = [
					READ => $permission[READ] == 0 ? $user['permissions'][$permissionSubject][$permissionSubjectId][READ] : $permission[READ],
					WRITE => $permission[WRITE] == 0 ? $user['permissions'][$permissionSubject][$permissionSubjectId][WRITE] : $permission[WRITE],
					DELETE => $permission[DELETE] == 0 ? $user['permissions'][$permissionSubject][$permissionSubjectId][DELETE] : $permission[DELETE],
					ACCESS_READ => $permission[ACCESS_READ] == 0 ? $user['permissions'][$permissionSubject][$permissionSubjectId][ACCESS_READ] : $permission[ACCESS_READ],
					ACCESS_WRITE => $permission[ACCESS_WRITE] == 0 ? $user['permissions'][$permissionSubject][$permissionSubjectId][ACCESS_WRITE] : $permission[ACCESS_WRITE],
					ACCESS_CHANGE => $permission[ACCESS_CHANGE] == 0 ? $user['permissions'][$permissionSubject][$permissionSubjectId][ACCESS_CHANGE] : $permission[ACCESS_CHANGE],
				];
			}
		}
	}

	static public function checkPermission(string $subject, int $subject_id=0, string $accessType=READ, array $user=[]): bool {
		if (isset($user) && is_array($user) && $user) {
			$currentUser = $user;
		} else {
			$currentUser = static::currentUser();
		}
		if (!$currentUser || !$currentUser['id']) {
			return false;
		}

		if (isset($currentUser['groups']) && is_array($currentUser['groups']) && isset($currentUser['groups'][\Groups::ADMIN_GROUP_ID])) {
			return true;
		}
		if (!in_array($accessType, [READ, WRITE, DELETE, ACCESS_READ, ACCESS_WRITE, ACCESS_CHANGE])) {
			return false;
		}
		if (!isset($currentUser['permissions']) || !is_array($currentUser['permissions'])) {
			return false;
		}

		if (!isset($currentUser['permissions'][$subject])) {
			return false;
		}
		if (!is_array($currentUser['permissions'][$subject])) {
			return false;
		}

		if ($subject_id == -1) {
			foreach($currentUser['permissions'][$subject] as $permissionSubjectId => $permission) {
				if (!is_array($permission)) continue;
				if (isset($permission[$accessType]) && $permission[$accessType] == 1) {
					return true;
				}
			}
		} elseif ($subject_id == 0) {
			if (!isset($currentUser['permissions'][$subject][0]) || !is_array($currentUser['permissions'][$subject][0])) {
				return false;
			}
			if ($currentUser['permissions'][$subject][0][$accessType] == 1) {
				return true;
			}
		} else {
			if (isset($currentUser['permissions'][$subject][0]) && is_array($currentUser['permissions'][$subject][0])) {
				if ($currentUser['permissions'][$subject][0][$accessType] == 1) {
					return true;
				}
			}
			if (!isset($currentUser['permissions'][$subject][$subject_id]) || !is_array($currentUser['permissions'][$subject][$subject_id])) {
				return false;
			}
			if ($currentUser['permissions'][$subject][$subject_id][$accessType] == 1) {
				return true;
			}
		}
		return false;
	}

	static public function getAllSubjectPermissions(string $subject, array $user=[]): array {
		if (isset($user) && is_array($user) && $user) {
			$currentUser = $user;
		} else {
			$currentUser = static::currentUser();
		}
		if (!$currentUser || !$currentUser['id']) {
			return [];
		}
		if (!isset($currentUser['permissions'][$subject])) {
			return [];
		}
		if (!is_array($currentUser['permissions'][$subject])) {
			return [];
		}
		return $currentUser['permissions'][$subject];
	}


	static public function requestPermission(string $subject, int $subject_id=0, string $accessType=READ, array $user=[]): void {
		if (isset($user) && is_array($user) && $user) {
			$currentUser = $user;
		} else {
			$currentUser = static::currentUser();
		}
		if (!$currentUser || !$currentUser['id']) {
			common_redirect('/login/');
			return;
		}
		if (!static::checkPermission($subject, $subject_id, $accessType, $currentUser)) {
			e403();
		}
	}

	static private function change_server_cookies(): void {
		if (!static::$current_user) return;
		if (isset($_GET['lang']) && $_GET['lang'] != '') {
			if (static::get_server_cookie('lang') != strtolower($_GET['lang'])) {
				static::set_server_cookie('lang', strtolower($_GET['lang']));
			}
		}
		\T::setCurrentLanguage(static::get_server_cookie('lang', \T::getCurrentLanguage()));


		if (isset($_GET['per_page'])) {
			if (in_array(intval($_GET['per_page']), \Config::getInstance()->per_page_counts)) {
				if (static::get_server_cookie('per_page') != intval($_GET['per_page'])) {
					static::set_server_cookie('per_page', intval($_GET['per_page']));
				}
			}
		}


		foreach(static::$current_user['cookie'] as $key => $value) {
			$_COOKIE[$key] = $value;
		}
		if (!isset($_COOKIE['per_page']) || $_COOKIE['per_page'] < 1) $_COOKIE['per_page'] = 10;
	}


	static public function session_init(bool $WithoutRedirect=false): bool {
		static::$current_user = [];
		do {
			static::get_session_id();
			if (!static::$sessionId) {
				break;
			}

			$session = static::get(['id' => static::$sessionId]);
			if (!$session) break;
			if ($session['sessionExpireTime'] < time()) break;
			$userId = '';
			if (!empty($session['userId'])) {
				$userId = $session['userId'];
			}
			if (!$userId) break;
			$user = Users::get(['id' => $userId]);
			if (!isset($user) || !is_array($user) || !$user) break;
			if ($user['status'] != \Users::STATUS_ACTIVE) break;
			$user['cookie'] = json_decode($user['cookie']??'{}', true);
			static::getPermissionsForUser($user);
			static::$current_user = $user;
			static::$originalUserId = $user['id'];
			static::$currentUserId = $user['id'];

			$currentIP = static::client_ip();
			if (!isset(static::$current_user['cookie']['client_ip'])) static::$current_user['cookie']['client_ip'] = '';
			if (static::$current_user['cookie']['client_ip'] != $currentIP) {
				\Logs::log('IP Changed',\Logs::ACTION_LOGIN,'user', static::$current_user['id'],[
					'ip' => [
						'old' => static::$current_user['cookie']['client_ip'],
						'new' => $currentIP,
					]
				]);
				static::$current_user['cookie']['client_ip'] = $currentIP;
				static::set_server_cookie('client_ip', $currentIP);
			}

			// IMPERSONATE_USER
			if ($session['sessionJsonData'] && is_string($session['sessionJsonData'])) {
				$jsonData = json_decode($session['sessionJsonData'], true);
				if (is_array($jsonData) && isset($jsonData['impersonateUserId']) && $jsonData['impersonateUserId']) {
					if (static::checkPermission(\Permissions::IMPERSONATE_USER, $jsonData['impersonateUserId'], READ, $user)) {
						$impersonateUser = Users::get(['id' => $jsonData['impersonateUserId']]);
						if (isset($impersonateUser) && is_array($impersonateUser) && $impersonateUser && $impersonateUser['status'] == \Users::STATUS_ACTIVE) {
							$user = $impersonateUser;
							$user['cookie'] = json_decode($user['cookie']??'{}', true);
							static::getPermissionsForUser($user);
							static::$current_user = $user;
							static::$currentUserId = $user['id'];
						}
					}
				}
			}

			// Update Session
			if ($session['sessionExpireTime'] < time() + static::$sessionLifeTime - 5*60) {
				$sessionUpdate = [
					'id' => $session['id'],
					'sessionExpireTime' => time() + static::$sessionLifeTime,
					'_mode' => \DB\Common::CSMODE_UPDATE,
				];
				static::save($sessionUpdate);
			}

		} while(0);

		if (!static::$current_user) {
			if ($WithoutRedirect) return false;
			setcookie('target', urlencode($_SERVER['REQUEST_URI']), time() + 86400, '/');
			$_COOKIE['target'] = urlencode($_SERVER['REQUEST_URI']);
			common_redirect('/login/');
			return false;
		}
		static::change_server_cookies();

		if (!$WithoutRedirect && \Config::getInstance()->app_signin_active && static::$current_user['flags'] & \Users::FLAGS_NEED_CHANGE_PASSWORD) {
			if (strpos($_SERVER['REQUEST_URI'],'/settings/') === false) {
				common_redirect('/settings/');
				return true;
			}
		}

		return true;
	}
}