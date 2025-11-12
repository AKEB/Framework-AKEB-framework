<?php

namespace Websocket;

class Notifications extends \Websocket_Parent implements \WebsocketWithAuth_Interface {
	public function Run(): mixed {
		$user = \Sessions::currentUser();
		if ($user && $user['id']) {
			if ($this->action == 'notifications') {
				$messages = [];
				$count = 0;
				$data = \Notifications::data(['user_id' => $user['id']], sql_pholder(' ORDER BY create_time ASC LIMIT 20'));
				if ($data) {
					foreach($data as $item) {
						$item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
						if ($item['read_time']) {
							$item['read_time'] = date('Y-m-d H:i:s', $item['read_time']);
						}
						if ($item['send_time']) {
							$item['send_time'] = date('Y-m-d H:i:s', $item['send_time']);
						}
						$messages[] = $item;
						if (!$item['read_time']) $count++;
						if (!$item['send_time']) {
							\Notifications::save([
								'id' => $item['id'],
								'send_time' => time(),
								'_mode' => \DB\Common::CSMODE_UPDATE,
							]);
						}
					}
				}
				return ['count' => $count, 'messages' => $messages];
			} else if ($this->action == 'notifications_read') {
				$notification_id = $this->params['id'] ?? 0;
				if ($notification_id) {
					$notification = \Notifications::get(['id' => $notification_id]);
					if ($notification && $notification['id']) {
						$notification_id = \Notifications::save([
							'id' => $notification['id'],
							'read_time' => time(),
							'_mode' => \DB\Common::CSMODE_UPDATE,
						]);
					}
				}
				return true;
			} else if ($this->action == 'notifications_delete') {
				$notification_id = $this->params['id'] ?? 0;
				if ($notification_id) {
					$notification = \Notifications::get(['id' => $notification_id]);
					if ($notification && $notification['id']) {
						\Notifications::delete(['id' => $notification['id']]);
					}
				}
				return true;
			}
		}
		return ['error' => 'Unauthorized', 'status' => 401];
	}
}