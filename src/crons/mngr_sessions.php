<?php

set_time_limit(0);
ini_set('memory_limit', '1024M');
if (isset($_ENV['SERVER_ROOT']) && $_ENV['SERVER_ROOT']) {
	$PWD = $_ENV['SERVER_ROOT'];
} else {
	$PWD = __DIR__."/../../vendor";
}
error_log($PWD."/autoload.php");
require_once($PWD."/autoload.php");

$__worker_sleep = 3600;

$__worker_title = strval($_SERVER['argv'][0] ?? 'worker');
$__worker_number = intval($_SERVER['argv'][1] ?? 0);
$__worker_pid = getmypid();
$__worker_errorlog_prefix = "$__worker_title"."[$__worker_number]"."($__worker_pid): ";

error_log("$__worker_errorlog_prefix: Started");
$__start_time = time();

\Sessions::delete(false, ' AND `sessionExpireTime` < UNIX_TIMESTAMP() + 600 ');

$__work_time = time() - $__start_time;
$sleepTime = max($__worker_sleep - $__work_time,0);
error_log("$__worker_errorlog_prefix: Finished $__work_time sec; sleep " . intval($sleepTime) . " sec");
echo "sleep " . intval($sleepTime);
