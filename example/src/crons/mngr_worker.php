<?php
require_once("../autoload.php");
$mngr = new \MNGR(60, '32M');

error_log(\T::Framework_ServerVersion()."=".constant('SERVER_VERSION'));
sleep(2);

