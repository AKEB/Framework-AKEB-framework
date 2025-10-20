<?php

class Test extends \Websocket_Parent {
	public function Run(): mixed {
		if ($this->action == 'test') {
			return 'Test response message';
		} else if ($this->action == 'notification_test') {
			\Notifications::sendNotification('Test Message', 'This is a test message from the server');
			return true;
		}
		return false;
	}
}