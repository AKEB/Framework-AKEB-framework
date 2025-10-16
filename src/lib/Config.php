<?php

class Config {
	public readonly string $timezone;
	public readonly string $password_salt;

	public readonly string $mysql_host;
	public readonly int $mysql_port;
	public readonly string $mysql_username;
	public readonly string $mysql_password;
	public readonly string $mysql_db_name;
	public readonly bool $mysql_dont_use_slave;
	public readonly string $mysql_slave_host;
	public readonly int $mysql_slave_port;
	public readonly string $mysql_slave_username;
	public readonly string $mysql_slave_password;
	public readonly string $mysql_slave_db_name;


	public readonly bool $app_signin_active;
	public readonly bool $app_signup_active;
	public readonly bool $app_debug;

	public readonly string $smtp_host;
	public readonly string $smtp_port;
	public readonly string $smtp_username;
	public readonly string $smtp_password;
	public readonly bool $smtp_tls;
	public readonly bool $smtp_ssl;

	public readonly string $oidc_provider;
	public readonly string $oidc_client_id;
	public readonly string $oidc_client_secret;
	public readonly string $oidc_button;
	public readonly string $oidc_scope;
	public readonly bool $oidc_register;

	public readonly string $oauth_client_id;
	public readonly string $oauth_client_secret;
	public readonly string $oauth_authorization_endpoint;
	public readonly string $oauth_token_endpoint;
	public readonly string $oauth_userinfo_endpoint;
	public readonly string $oauth_button;
	public readonly string $oauth_scope;
	public readonly bool $oauth_register;

	public readonly string $memcached_host;
	public readonly int $memcached_port;

	public readonly array $per_page_counts;

	private static $_instance;

	// public function __construct() {
	// 	$this->server_url = strval($_ENV['SERVER_URL'] ?? '');
	// }

	private function __construct() {}
	private function __clone() {}
	public function __wakeup() {}

	public static function getInstance(): self {
		if (self::$_instance === null) {
			self::$_instance = new self;

			// General
			self::$_instance->timezone = strval($_ENV['TZ'] ?? 'UTC');
			self::$_instance->password_salt = strval($_ENV['PASSWORD_SALT'] ?? "bHchLzC3B99Ss2ghc2gkDdtgCG7vKtoj");

			// Mysql
			self::$_instance->mysql_host = strval($_ENV['MYSQL_HOST'] ?? 'localhost');
			self::$_instance->mysql_port = intval($_ENV['MYSQL_PORT'] ?? 3306);
			self::$_instance->mysql_username = strval($_ENV['MYSQL_USERNAME'] ?? 'root');
			self::$_instance->mysql_password = strval($_ENV['MYSQL_PASSWORD'] ?? '');
			self::$_instance->mysql_db_name = strval($_ENV['MYSQL_DB_NAME'] ?? 'example');
			self::$_instance->mysql_dont_use_slave = (isset($_ENV['MYSQL_DONT_USE_SLAVE']) && $_ENV['MYSQL_DONT_USE_SLAVE'] == 'false') ? false : true;
			self::$_instance->mysql_slave_host = strval($_ENV['MYSQL_SLAVE_HOST'] ?? self::$_instance->mysql_host);
			self::$_instance->mysql_slave_port = intval($_ENV['MYSQL_SLAVE_PORT'] ?? self::$_instance->mysql_port);
			self::$_instance->mysql_slave_username = strval($_ENV['MYSQL_SLAVE_USERNAME'] ?? self::$_instance->mysql_username);
			self::$_instance->mysql_slave_password = strval($_ENV['MYSQL_SLAVE_PASSWORD'] ?? self::$_instance->mysql_password);
			self::$_instance->mysql_slave_db_name = strval($_ENV['MYSQL_SLAVE_DB_NAME'] ?? self::$_instance->mysql_db_name);
			// APP
			self::$_instance->app_signin_active = (isset($_ENV['APP_SIGNIN_ACTIVE']) && $_ENV['APP_SIGNIN_ACTIVE'] == 'false') ? false : true;
			self::$_instance->app_signup_active = (isset($_ENV['APP_SIGNUP_ACTIVE']) && $_ENV['APP_SIGNUP_ACTIVE'] == 'false') ? false : true;
			self::$_instance->app_debug = (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] == 'true') ? true : false;
			// SMTP
			self::$_instance->smtp_host = strval($_ENV['SMTP_HOST'] ?? '');
			self::$_instance->smtp_port = intval($_ENV['SMTP_PORT'] ?? 25);
			self::$_instance->smtp_username = strval($_ENV['SMTP_USERNAME'] ?? '');
			self::$_instance->smtp_password = strval($_ENV['SMTP_PASSWORD'] ?? '');
			self::$_instance->smtp_tls = (isset($_ENV['SMTP_TLS']) && $_ENV['SMTP_TLS'] == 'true') ? true : false;
			self::$_instance->smtp_ssl = (isset($_ENV['SMTP_SSL']) && $_ENV['SMTP_SSL'] == 'true') ? true : false;

			// OIDC
			self::$_instance->oidc_provider = strval($_ENV['OPENIDCONNECT_PROVIDER'] ?? '');
			self::$_instance->oidc_client_id = strval($_ENV['OPENIDCONNECT_CLIENT_ID'] ?? '');
			self::$_instance->oidc_client_secret = strval($_ENV['OPENIDCONNECT_CLIENT_SECRET'] ?? '');
			self::$_instance->oidc_button = strval($_ENV['OPENIDCONNECT_BUTTON'] ?? '');
			self::$_instance->oidc_scope = strval($_ENV['OPENIDCONNECT_SCOPE'] ?? 'email profile openid');
			self::$_instance->oidc_register = (isset($_ENV['OPENIDCONNECT_REGISTER']) && $_ENV['OPENIDCONNECT_REGISTER'] == 'false') ? false : true;

			// OAuth
			self::$_instance->oauth_client_id = strval($_ENV['OAUTH_CLIENT_ID'] ?? '');
			self::$_instance->oauth_client_secret = strval($_ENV['OAUTH_CLIENT_SECRET'] ?? '');
			self::$_instance->oauth_authorization_endpoint = strval($_ENV['OAUTH_AUTHORIZATION_ENDPOINT'] ?? '');
			self::$_instance->oauth_token_endpoint = strval($_ENV['OAUTH_TOKEN_ENDPOINT'] ?? '');
			self::$_instance->oauth_userinfo_endpoint = strval($_ENV['OAUTH_USERINFO_ENDPOINT'] ?? '');
			self::$_instance->oauth_button = strval($_ENV['OAUTH_BUTTON'] ?? '');
			self::$_instance->oauth_scope = strval($_ENV['OAUTH_SCOPE'] ?? 'self_profile');
			self::$_instance->oauth_register = (isset($_ENV['OAUTH_REGISTER']) && $_ENV['OAUTH_REGISTER'] == 'false') ? false : true;

			// Memcached
			self::$_instance->memcached_host = strval($_ENV['MEMCACHED_HOST'] ?? '');
			self::$_instance->memcached_port = intval($_ENV['MEMCACHED_PORT'] ?? 11211);

			self::$_instance->per_page_counts = [5, 10, 20, 50, 100, 500, 1000];
		}
		return self::$_instance;
	}

	public function toArray() {
		return [
			'timezone' => $this->timezone,
			'password_salt' => $this->password_salt ? '*****************':'',

			'mysql_host' => $this->mysql_host,
			'mysql_port' => $this->mysql_port,
			'mysql_username' => $this->mysql_username,
			'mysql_password' => $this->mysql_password,
			'mysql_db_name' => $this->mysql_db_name,
			'mysql_dont_use_slave' => $this->mysql_dont_use_slave,
			'mysql_slave_host' => $this->mysql_slave_host,
			'mysql_slave_port' => $this->mysql_slave_port,
			'mysql_slave_username' => $this->mysql_slave_username,
			'mysql_slave_password' => $this->mysql_slave_password,
			'mysql_slave_db_name' => $this->mysql_slave_db_name,

			'app_signin_active' => $this->app_signin_active,
			'app_signup_active' => $this->app_signup_active,
			'app_debug' => $this->app_debug,

			'smtp_host' => $this->smtp_host,
			'smtp_port' => $this->smtp_port,
			'smtp_username' => $this->smtp_username,
			'smtp_password' => $this->smtp_password ? '*****************':'',
			'smtp_tls' => $this->smtp_tls,
			'smtp_ssl' => $this->smtp_ssl,

			'oidc_provider' => $this->oidc_provider,
			'oidc_client_id' => $this->oidc_client_id,
			'oidc_client_secret' => $this->oidc_client_secret ? '*****************':'',
			'oidc_button' => $this->oidc_button,
			'oidc_scope' => $this->oidc_scope,
			'oidc_register' => $this->oidc_register,

			'oauth_client_id' => $this->oauth_client_id,
			'oauth_client_secret' => $this->oauth_client_secret ? '*****************':'',
			'oauth_authorization_endpoint' => $this->oauth_authorization_endpoint,
			'oauth_token_endpoint' => $this->oauth_token_endpoint,
			'oauth_userinfo_endpoint' => $this->oauth_userinfo_endpoint,
			'oauth_button' => $this->oauth_button,
			'oauth_scope' => $this->oauth_scope,
			'oauth_register' => $this->oauth_register,

			'memcached_host' => $this->memcached_host,
			'memcached_port' => $this->memcached_port,

			'per_page_counts' => $this->per_page_counts,

		];
	}

	public function __debugInfo() {
		return $this->toArray();
	}

	public function __toString() {
		return json_encode($this->toArray());
	}
}