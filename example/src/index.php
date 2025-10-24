<?php
require_once("./autoload.php");

// Content Security Policy
\ContentSecurityPolicy::init();

// Add another Content Security Policy style-src
// \ContentSecurityPolicy::set_style_src([]);
// \ContentSecurityPolicy::add_style_src("'self'");
// \ContentSecurityPolicy::add_style_src("'unsafe-inline'");

// Add another Content Security Policy media-src
// \ContentSecurityPolicy::add_media_src('https://fonts.ninja/');

// Print Content Security Policy Header
\ContentSecurityPolicy::print_header();

// Add another permissions subject types
\Permissions::set_subject_type('worker', '\\Workers', 'Workers');

\Sessions::session_init(true); // This need to check Permissions and user language

// Set Application Settings
\Template::setProjectName("Example");
\Template::setTheme('auto');

\Template::addHeadMeta(['content' => "#6a11cb",'name' => 'msapplication-TileColor']);
\Template::addHeadMeta(['name' => 'theme-color','content' => "#6a11cb"]);

// Add another css files
\Template::addCSSFile('/css/main.css');

// Add another js files
\Template::addJSFile('/js/locale_'.\T::getCurrentLanguage().'.js');
\Template::addJSFile('/js/main.js');

\Template::addMenuItem(new \MenuItem('', \T::Menu_Home(), '/', null, null));
\Template::addMenuItem(new \MenuItem('bi bi-code-square', \T::Menu_Test(), '/test/', null, null));

// \Template::addMenuItem(new \MenuItem('bi bi-person', \T::Framework_Menu_Users(), '/users/', null,
// 	[
// 		new \MenuPermissionItem(\Users::PERMISSION_MANAGE_USERS, -1, READ),
// 		new \MenuPermissionItem(\Users::PERMISSION_CREATE_USER, 0, WRITE),
// 	]
// ));

// Add another Websocket item
\Websocket::addAction('test', '\\Test');
\Websocket::addAction('notification_test', '\\Test');
\Websocket::addAction('mattermost_test', '\\Test');

// ADD Another Routes
// \Routes::addRoute('/test/', '\\App\\Test');

new \Routing();

// Main Page
$app = new \App\Main();
$app->Run();
