<?php

class Routing {
	protected array $routes = [];

	protected function routes(): array {
		$this->routes = [
			'/admin/users/edit/(?P<user_id>\d+)/' => '/admin/users/edit/',
			'/admin/users/(?P<action>[^/]+)/(?P<user_id>\d+)/' => '/admin/users/',
			'/admin/users/(?P<user_id>\d+)/groups/' => '/admin/users/groups/',
			'/admin/users/(?P<user_id>\d+)/groups/(?P<action>[^/]+)/' => '/admin/users/groups/',
			'/admin/users/(?P<user_id>\d+)/groups/(?P<action>[^/]+)/(?P<group_id>\d+)/' => '/admin/users/groups/',

			'/admin/groups/(?P<action>[^/]+)/' => '/admin/groups/',
			'/admin/groups/(?P<action>[^/]+)/(?P<group_id>\d+)/' => '/admin/groups/',

			'/admin/permissions/(?P<subject>[^/]+)/(?P<subject_id>\d+)/' => '/admin/permissions/',

			'/admin/impersonate/(?P<user_id>\d+)/' => '/admin/impersonate/',

			'/settings/disable_2fa/' => '\\FrameworkApp\\Settings',

			'/settings/' => '\\FrameworkApp\\Settings',
			'/signup/' => '\\FrameworkApp\\Signup',
			'/login/' => '\\FrameworkApp\\Login',
			'/logout/' => '\\FrameworkApp\\Logout',
			'/forgot/' => '\\FrameworkApp\\Forgot',


		];
		return $this->routes;
	}

	public function __construct() {
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
			foreach($this->routes() as $route => $class) {
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