<?php
require_once("./autoload.php");

\Template::setProjectName("Example");

\Template::setMenuItems([
	[
		'title' => \T::Menu_Home(),
		'link'=>'/',
	],
	[
		'title' => \T::Menu_Test(),
		'link' => '/test/',
		'icon' => "bi bi-code-square"
		// 'permission' => \Sessions::checkPermission(\Permissions::ADMIN, 0, READ),
	],
]);


$routes = \Routes::getInstance();
// ADD Another Routes



$routing = new \Routing();


// Main Page
$app = new \App\Main();
$app->Run();
