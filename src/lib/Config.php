<?php

enum ConfigEnvType {
	case INT;
	case FLOAT;
	case BOOL;
	case STRING;
	case ARRAY;
	case OBJECT;
	case DEFAULT;
}

class Config {

	private static $_instance;

	private $_params = [];

	// public function __construct() {
	// 	$this->server_url = strval($_ENV['SERVER_URL'] ?? '');
	// }

	private array $_dont_use_keys = [
		'hostname',
		'php_version',
		'pwd',
		'mysql_root_password',
		'tz',
		'container',
		'home',
		'server_version',
		'shlvl',
		'nginx_port',
		'phpmyadmin_port',
		'path',
		'_',
		'user',
		'http_cookie',
		'http_priority',
		'http_accept_language',
		'http_accept_encoding',
		'http_referer',
		'http_sec_fetch_dest',
		'http_sec_fetch_user',
		'http_sec_fetch_mode',
		'http_sec_fetch_site',
		'http_accept',
		'http_user_agent',
		'http_upgrade_insecure_requests',
		'http_sec_ch_ua_platform',
		'http_sec_ch_ua_mobile',
		'http_sec_ch_ua',
		'http_cache_control',
		'http_x_real_ip',
		'http_x_forwarded_for',
		'http_x_forwarded_proto',
		'http_x_forwarded_scheme',
		'http_host',
		'redirect_status',
		'server_name',
		'server_port',
		'server_addr',
		'remote_user',
		'remote_port',
		'remote_addr',
		'server_software',
		'gateway_interface',
		'request_scheme',
		'server_protocol',
		'document_root',
		'document_uri',
		'request_uri',
		'script_name',
		'content_length',
		'content_type',
		'request_method',
		'query_string',
		'php_value',
		'script_filename',
		'fcgi_role',
	];

	private function __construct() {}
	private function __clone() {}
	public function __wakeup() {}

	public function __get($name): mixed {
		if (!array_key_exists(strtolower($name), $this->_params)) {
			return null;
		}
		return $this->_params[strtolower($name)];
	}

	public function get(string $name, ConfigEnvType $type=ConfigEnvType::DEFAULT, $default_value=null): mixed {
		$value = $this->__get($name);
		if ($value === null) {
			$value = $default_value;
		}
		switch ($type) {
			case ConfigEnvType::INT:
				$value = (int) $value;
				break;
			case ConfigEnvType::FLOAT:
				$value = (float) $value;
				break;
			case ConfigEnvType::BOOL:
				$value = ($value == 'true') ? true : false;
				break;
			case ConfigEnvType::STRING:
				$value = (string) $value;
				break;
			case ConfigEnvType::ARRAY:
				$value = (array) $value;
				break;
			case ConfigEnvType::OBJECT:
				$value = (object) $value;
				break;
			default:
				break;
		}
		return $value;
	}

	private function _get_params() {
		$this->_params = [
			// General
			'timezone' => $this->getenv('TZ', ConfigEnvType::STRING, 'UTC'),
			'password_salt' => $this->getenv('PASSWORD_SALT', ConfigEnvType::STRING, 'bHchLzC3B99Ss2ghc2gkDdtgCG7vKtoj'),
			// MySQL
			'mysql_debug' => $this->getenv('MYSQL_DEBUG', ConfigEnvType::BOOL, false),
			'mysql_host' => $this->getenv('MYSQL_HOST', ConfigEnvType::STRING, 'localhost'),
			'mysql_port' => $this->getenv('MYSQL_PORT', ConfigEnvType::INT, 3306),
			'mysql_username' => $this->getenv('MYSQL_USERNAME', ConfigEnvType::STRING, 'root'),
			'mysql_password' => $this->getenv('MYSQL_PASSWORD', ConfigEnvType::STRING, ''),
			'mysql_db_name' => $this->getenv('MYSQL_DB_NAME', ConfigEnvType::STRING, 'example'),
			'mysql_dont_use_slave' => $this->getenv('MYSQL_DONT_USE_SLAVE', ConfigEnvType::BOOL, true),
			// APP
			'app_signin_active' => $this->getenv('APP_SIGNIN_ACTIVE', ConfigEnvType::BOOL, true),
			'app_signup_active' => $this->getenv('APP_SIGNUP_ACTIVE', ConfigEnvType::BOOL, true),
			'app_debug' => $this->getenv('APP_DEBUG', ConfigEnvType::BOOL, false),
			// SMTP
			'smtp_host' => $this->getenv('SMTP_HOST', ConfigEnvType::STRING, ''),
			'smtp_port' => $this->getenv('SMTP_PORT', ConfigEnvType::INT, 25),
			'smtp_username' => $this->getenv('SMTP_USERNAME', ConfigEnvType::STRING, ''),
			'smtp_password' => $this->getenv('SMTP_PASSWORD', ConfigEnvType::STRING, ''),
			'smtp_tls' => $this->getenv('SMTP_TLS', ConfigEnvType::BOOL, false),
			'smtp_ssl' => $this->getenv('SMTP_SSL', ConfigEnvType::BOOL, false),
			// OIDC
			'openidconnect_provider' => $this->getenv('OPENIDCONNECT_PROVIDER', ConfigEnvType::STRING, ''),
			'openidconnect_client_id' => $this->getenv('OPENIDCONNECT_CLIENT_ID', ConfigEnvType::STRING, ''),
			'openidconnect_client_secret' => $this->getenv('OPENIDCONNECT_CLIENT_SECRET', ConfigEnvType::STRING, ''),
			'openidconnect_button' => $this->getenv('OPENIDCONNECT_BUTTON', ConfigEnvType::STRING, ''),
			'openidconnect_scope' => $this->getenv('OPENIDCONNECT_SCOPE', ConfigEnvType::STRING, 'email profile openid'),
			'openidconnect_register' => $this->getenv('OPENIDCONNECT_REGISTER', ConfigEnvType::BOOL, true),
			// OAuth
			'oauth_client_id' => $this->getenv('OAUTH_CLIENT_ID', ConfigEnvType::STRING, ''),
			'oauth_client_secret' => $this->getenv('OAUTH_CLIENT_SECRET', ConfigEnvType::STRING, ''),
			'oauth_authorization_endpoint' => $this->getenv('OAUTH_AUTHORIZATION_ENDPOINT', ConfigEnvType::STRING, ''),
			'oauth_token_endpoint' => $this->getenv('OAUTH_TOKEN_ENDPOINT', ConfigEnvType::STRING, ''),
			'oauth_userinfo_endpoint' => $this->getenv('OAUTH_USERINFO_ENDPOINT', ConfigEnvType::STRING, ''),
			'oauth_button' => $this->getenv('OAUTH_BUTTON', ConfigEnvType::STRING, ''),
			'oauth_scope' => $this->getenv('OAUTH_SCOPE', ConfigEnvType::STRING, 'self_profile'),
			'oauth_register' => $this->getenv('OAUTH_REGISTER', ConfigEnvType::BOOL, true),
			// Memcached
			'memcached_host' => $this->getenv('MEMCACHED_HOST', ConfigEnvType::STRING, ''),
			'memcached_port' => $this->getenv('MEMCACHED_PORT', ConfigEnvType::INT, 11211),
			// Telegram
			'telegram_bot_token' => $this->getenv('TELEGRAM_BOT_TOKEN', ConfigEnvType::STRING, ''),
			// Other
			'per_page_counts' => [5, 10, 20, 50, 100, 500, 1000],

		];

		$this->_params['mysql_slave_host'] = $this->getenv('MYSQL_SLAVE_HOST', ConfigEnvType::STRING, $this->_params['mysql_host']);
		$this->_params['mysql_slave_port'] = $this->getenv('MYSQL_SLAVE_PORT', ConfigEnvType::INT, $this->_params['mysql_port']);
		$this->_params['mysql_slave_username'] = $this->getenv('MYSQL_SLAVE_USERNAME', ConfigEnvType::STRING, $this->_params['mysql_username']);
		$this->_params['mysql_slave_password'] = $this->getenv('MYSQL_SLAVE_PASSWORD', ConfigEnvType::STRING, $this->_params['mysql_password']);
		$this->_params['mysql_slave_db_name'] = $this->getenv('MYSQL_SLAVE_DB_NAME', ConfigEnvType::STRING, $this->_params['mysql_db_name']);

		$all_env = getenv();
		foreach ($all_env as $key=>$value) {
			if (in_array(strtolower($key), $this->_dont_use_keys)) {
				continue;
			}
			if (!array_key_exists(strtolower($key), $this->_params)) {
				$this->_params[strtolower($key)] = $value;
			}
		}
	}

	private function getenv(string $key, ConfigEnvType $type=ConfigEnvType::DEFAULT, $default_value=null): mixed {
		$value = getenv(strtoupper($key));
		if ($value === false) {
			$value = $default_value;
		}
		switch ($type) {
			case ConfigEnvType::INT:
				$value = (int) $value;
				break;
			case ConfigEnvType::FLOAT:
				$value = (float) $value;
				break;
			case ConfigEnvType::BOOL:
				$value = ($value == 'true') ? true : false;
				break;
			case ConfigEnvType::STRING:
				$value = (string) $value;
				break;
			case ConfigEnvType::ARRAY:
				$value = (array) $value;
				break;
			case ConfigEnvType::OBJECT:
				$value = (object) $value;
				break;
			default:
				break;
		}
		return $value;
	}

	public static function getInstance(): self {
		if (self::$_instance === null) {
			self::$_instance = new self;
			self::$_instance->_get_params();
		}
		return self::$_instance;
	}

	private function _hide_password(?string $password): string {
		if (!$password) return '';
		$len = mb_strlen($password);
		$dont_hide_length = max(intval(($len*25/100)/2),1);
		return substr($password, 0, $dont_hide_length) . str_repeat('*', $len - 2*$dont_hide_length) . substr($password, -$dont_hide_length, $dont_hide_length);
	}

	public function toArray() {
		$params = $this->_params;
		$params['mysql_password'] = $this->_hide_password($this->mysql_password);
		$params['mysql_slave_password'] = $this->_hide_password($this->mysql_slave_password);
		$params['password_salt'] = $this->_hide_password($this->password_salt);
		$params['smtp_password'] = $this->_hide_password($this->smtp_password);
		$params['oauth_client_secret'] = $this->_hide_password($this->oauth_client_secret);
		$params['openidconnect_client_secret'] = $this->_hide_password($this->openidconnect_client_secret);
		return $params;
	}

	public function __debugInfo() {
		return $this->toArray();
	}

	public function __toString() {
		return json_encode($this->toArray());
	}
}