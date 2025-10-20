<?php

class Notifications extends \DB\MySQLObject{
	static public $table = 'notifications';
	const LOGS_OBJECT = 'Notifications';


	public static function sendNotification(string $title, string $body, ?int $user_id=null, bool $withoutToast=false): void {
		if (!isset($user_id) || !$user_id) {
			$user_id = \Sessions::currentUser()['id'];
		}
		if (!$title && !$body) {
			return;
		}
		$params = [
			'title' => $title,
			'body' => $body,
			'user_id' => $user_id,
			'create_time' => time(),
			'send_time' => $withoutToast ? time() : 0,
			'read_time' => 0,
			'_mode' => \DB\Common::CSMODE_INSERT,
		];
		$id = \Notifications::save($params);
		if ($id > 0) {
			$log_id = \Logs::create_log(\Notifications::LOGS_OBJECT, $id, $params);
			\Logs::add_tag($log_id, \Users::LOGS_OBJECT, $user_id);
		}
		return;
	}

}