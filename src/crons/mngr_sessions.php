<?php

if (isset($_ENV['SERVER_ROOT']) && $_ENV['SERVER_ROOT']) {
	$PWD = $_ENV['SERVER_ROOT'];
} else {
	$PWD = __DIR__."/../../vendor";
}
require_once($PWD."/autoload.php");
$mngr = new \MNGR(3600, '32M');

\Sessions::delete(false, ' AND `session_expire_time` < UNIX_TIMESTAMP() - 600 ');

\Users::getDatabase()->execSQL("UPDATE `".(\Users::$table)."` SET `reset_token`='', `reset_token_expires`=0, `update_time`=UNIX_TIMESTAMP() WHERE `reset_token_expires` < UNIX_TIMESTAMP() - 600;");

\Notifications::delete(false, ' AND `read_time` < UNIX_TIMESTAMP() - 30*86400 ');
