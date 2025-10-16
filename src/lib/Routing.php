<?php

class Routing {
	public function __construct() {
		$this->setRoutes();
		$this->Run();
	}

	private function setRoutes() {
		\Routes::addRoute('/admin/users/edit/(?P<user_id>\d+)/', '\\FrameworkApp\\Admin\\Users\\Edit');
		\Routes::addRoute('/admin/users/edit/', '\\FrameworkApp\\Admin\\Users\\Edit');

		\Routes::addRoute('/admin/users/(?P<action>[^/]+)/(?P<user_id>\d+)/', '\\FrameworkApp\\Admin\\Users');
		\Routes::addRoute('/admin/users/', '\\FrameworkApp\\Admin\\Users');

		\Routes::addRoute('/admin/users/(?P<user_id>\d+)/groups/', '\\FrameworkApp\\Admin\\Users\\Groups');
		\Routes::addRoute('/admin/users/(?P<user_id>\d+)/groups/(?P<action>[^/]+)/', '\\FrameworkApp\\Admin\\Users\\Groups');
		\Routes::addRoute('/admin/users/(?P<user_id>\d+)/groups/(?P<action>[^/]+)/(?P<group_id>\d+)/', '\\FrameworkApp\\Admin\\Users\\Groups');
		\Routes::addRoute('/admin/users/groups/', '\\FrameworkApp\\Admin\\Users\\Groups');

		\Routes::addRoute('/admin/groups/(?P<action>[^/]+)/', '\\FrameworkApp\\Admin\\Groups');
		\Routes::addRoute('/admin/groups/(?P<action>[^/]+)/(?P<group_id>\d+)/', '\\FrameworkApp\\Admin\\Groups');
		\Routes::addRoute('/admin/groups/', '\\FrameworkApp\\Admin\\Groups');

		\Routes::addRoute('/admin/permissions/(?P<subject>[^/]+)/(?P<subject_id>\d+)/', '\\FrameworkApp\\Admin\\Permissions');
		\Routes::addRoute('/admin/permissions/', '\\FrameworkApp\\Admin\\Permissions');

		\Routes::addRoute('/admin/impersonate/(?P<user_id>\d+)/', '\\FrameworkApp\\Admin\\Impersonate');
		\Routes::addRoute('/admin/impersonate/', '\\FrameworkApp\\Admin\\Impersonate');

		\Routes::addRoute('/settings/disable_2fa/', '\\FrameworkApp\\Settings');
		\Routes::addRoute('/settings/', '\\FrameworkApp\\Settings');

		\Routes::addRoute('/forgot/', '\\FrameworkApp\\Forgot');

		\Routes::addRoute('/login/', '\\FrameworkApp\\Login');

		\Routes::addRoute('/logout/', '\\FrameworkApp\\Logout');

		\Routes::addRoute('/signup/', '\\FrameworkApp\\Signup');

	}


	private function Run() {
		if (
			isset($_SERVER['SCRIPT_FILENAME']) &&
			isset($_SERVER['SCRIPT_NAME']) &&
			isset($_SERVER['DOCUMENT_ROOT']) &&
			$_SERVER['SCRIPT_FILENAME'] != $_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_NAME']
		) {

			$DOCUMENT_URI = $_SERVER['DOCUMENT_URI'];
			$path_to_class = '';
			$ARGS = [];
			// var_dump($_SERVER['DOCUMENT_URI']);
			$routes = \Routes::getRoutes();
			foreach($routes as $route => $class) {
				// var_dump($route);
				if ($_SERVER['DOCUMENT_URI'] == $route) {
					if (strpos($class, '\\') !== false && class_exists($class)) {
						$path_to_class = $class;
					} else {
						$DOCUMENT_URI = $class;
					}
					$ARGS = [];
					break;
				}
				$matches = [];
				$status = preg_match('~'.$route.'$~', $_SERVER['DOCUMENT_URI'], $matches);
				if (!$status) continue;
				unset($matches[0]);
				if (strpos($class, '\\') !== false && class_exists($class)) {
					$path_to_class = $class;
				} else {
					$DOCUMENT_URI = $class;
				}
				$ARGS = array_filter(
					$matches,
					fn($key) => !is_int($key),
					ARRAY_FILTER_USE_KEY
				);
				break;
			}
			if (!$path_to_class) {
				$path = explode('/', $DOCUMENT_URI);
				$folders = ['App'];
				foreach($path as $item) {
					if ($item) {
						// First letter uppercase
						$folders[] = mb_convert_case(trim($item), MB_CASE_TITLE);
					}
				}
				$path_to_class = '\\'. implode('\\',$folders);
			}

			do {
				if (!class_exists($path_to_class)) {
					break;
				}
				$object = new $path_to_class();
				if (!($object instanceof \Routing_Interface)) {
					break;
				}
				if (!method_exists($object, 'Run')) {
					break;
				}
				call_user_func([$object, 'Run'], ...$ARGS);
				exit;
			} while(false);
			$this->e404();
		}
	}

	public function e404() {
		header('HTTP/1.1 404 Page Not Found');
		$template = new \Template(false);
		echo '<div class="row d-flex justify-content-center align-middle text-center mt-5"><h1>404<br/>Page Not Found!</h1></div>';
		exit;
	}
}