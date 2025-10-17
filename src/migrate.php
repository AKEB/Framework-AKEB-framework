<?php

if (isset($_ENV['SERVER_ROOT']) && $_ENV['SERVER_ROOT']) {
	$PWD = $_ENV['SERVER_ROOT'];
} else {
	$PWD = __DIR__."/../vendor";
}
require_once($PWD."/autoload.php");
set_time_limit(0);

$database = new \DB\Initialize(
	\Config::getInstance()->mysql_host,
	\Config::getInstance()->mysql_username,
	\Config::getInstance()->mysql_password,
	\Config::getInstance()->mysql_db_name,
	\Config::getInstance()->mysql_port
);

error_log("Waiting MySQL");
$sleep_time = 2;
$max_wait_time = 60;
$status = false;
$start_time = time();
do {
	error_log("Check MySQL connection");
	if ($database->try_connect()) {
		$status = true;
	} else {
		error_log("Sleep");
		sleep($sleep_time);
	}
} while(!$status && time() - $start_time < $max_wait_time);

error_log("Migrates Running");
$database->init();

if (isset($_SERVER['argv']) && is_array($_SERVER['argv']) && isset($_SERVER['argv'][1])) {
	$action = $_SERVER['argv'][1];
	if ($action == 'rollback') {
		\Migrate::rollback(array_slice($_SERVER['argv'], 2));
	}
} else {
	\Migrate::apply();
}

error_log("Migrates Finished");
