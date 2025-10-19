<?php

class Websocket extends \Routing_Parent implements \Routing_Interface {

	private static array $actions = [];


	public static function addAction(string $action, string $class) {
		static::$actions[$action] = $class;
	}

	public function __construct() {
		static::addAction('notifications', '\\Websocket\\Notifications');
		static::addAction('notifications_read', '\\Websocket\\Notifications');
		static::addAction('notifications_delete', '\\Websocket\\Notifications');
		static::addAction('getUserName', '\\Websocket\\GetUserName');
	}

	public function Run() {
		$session_uid = strval(trim($_POST['session_uid']??''));
		$action = strval(trim($_POST['action']));
		$params = isset($_POST['params']) ? $_POST['params'] : [];
		if ($params && is_string($params)) $params = json_decode($params, true);
		if (!$params) $params = [];
		$response = [
			'action' => $action,
			'request_microtime' => microtime(true),
			'status' => 200,
		];

		do {
			switch($action) {
				case 'ping':
					$response['message'] = 'pong';
					break;
				case 'pong':
					$response['message'] = 'ping';
					break;
				default:
					do {
						if (!isset(static::$actions[$action])) break;
						if (!class_exists(static::$actions[$action])) break;
						$path_to_class = static::$actions[$action];
						$object = new $path_to_class($action, $params);
						if (
							!($object instanceof \Websocket_Interface)
						) break;
						if ($object instanceof \WebsocketWithAuth_Interface) {
							if (!$object->auth_check($session_uid)) {
								$response['status'] = 401;
								$response['error'] = "session_uid fail";
								break 2;
							}
						}
						if (!method_exists($object, 'Run')) break;
						$response['message'] = call_user_func([$object, 'Run']);
						break 2;
					} while(false);
					$response['status'] = 404;
					$response['error'] = "action fail";
					break;
			}
		} while(false);


		$response['server_time'] = time();
		$response['server_microtime'] = microtime(true);
		echo json_encode($response);
		return;
	}

}
