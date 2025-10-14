<?php
error_reporting(E_ALL &~ E_NOTICE);

if (!isset($_POST) || !is_array($_POST)) $_POST = [];
if (!isset($_GET) || !is_array($_GET)) $_GET = [];
if (!isset($_COOKIE) || !is_array($_COOKIE)) $_COOKIE = [];
if (!isset($_SERVER) || !is_array($_SERVER)) $_SERVER = [];
if (!isset($_REQUEST) || !is_array($_REQUEST)) $_REQUEST = [];
if (!isset($_FILES) || !is_array($_FILES)) $_FILES = [];
if (!isset($_ENV) || !is_array($_ENV)) $_ENV = [];
if (!isset($_SESSION) || !is_array($_SESSION)) $_SESSION = [];
if (!isset($_GLOBALS) || !is_array($_GLOBALS)) $_GLOBALS = [];

srand(intval(round(microtime(true)*100)));
mt_srand(intval(round(microtime(true)*100)));

\Config::getInstance();
date_default_timezone_set(\Config::getInstance()->timezone);

new \CSP();
new \T([
	'en' => 'vendor/akeb/framework/src/lang/framework_en.yml',
	'ru' => 'vendor/akeb/framework/src/lang/framework_ru.yml',
]);
new \T();

global $error;
$error = '';
global $success;
$success = '';

if (isset($_GET['error']) && $_GET['error']) {
	$error = $_GET['error'];
} else if (isset($_POST['error']) && $_POST['error']) {
	$error = $_POST['error'];
}

if (isset($_GET['success']) && $_GET['success']) {
	$success = $_GET['success'];
} else if (isset($_POST['success']) && $_POST['success']) {
	$success = $_POST['success'];
}

\GoogleAuthenticator::$SECRET_LENGTH = 24;

global $dbs_slaves, $dbs_masters;
global $db, $db_slave;
global $common_cache, $common_cache_active;
global $use_slave;

$db = new \DB\Database(
	(\Config::getInstance()->mysql_host).':'.(\Config::getInstance()->mysql_port),
	\Config::getInstance()->mysql_db_name,
	\Config::getInstance()->mysql_username,
	\Config::getInstance()->mysql_password,
	'UTF8'
);

$db_slave = new \DB\Database(
	(\Config::getInstance()->mysql_slave_host).':'.(\Config::getInstance()->mysql_slave_port),
	\Config::getInstance()->mysql_slave_db_name,
	\Config::getInstance()->mysql_slave_username,
	\Config::getInstance()->mysql_slave_password,
	'UTF8'
);
if (\Config::getInstance()->mysql_dont_use_slave) {
	$db_slave->do_not_check_slave_status = true;
}
$db_slave->database_master = false;
$dbs_slaves[\Config::getInstance()->mysql_db_name] = $db_slave;
$dbs_masters[\Config::getInstance()->mysql_slave_db_name] = $db;
