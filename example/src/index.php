<?php
require_once("./autoload.php");

\Sessions::session_init(true); // This need to check Permissions and user language

\Template::setProjectName("Example");
\Template::setTheme('auto');

\Template::addCSSFile('/css/main.css');

\Template::addJSFile('/js/locale_'.\T::getCurrentLanguage().'.js');
\Template::addJSFile('/js/main.js');

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

// ADD Another Routes
// \Routes::addRoute('/test/', '\\App\\Test');
new \Routing();

// Main Page
$app = new \App\Main();
$app->Run();
