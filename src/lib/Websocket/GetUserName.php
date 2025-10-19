<?php

namespace Websocket;

class GetUserName extends \Websocket_Parent implements \WebsocketWithAuth_Interface {
	public function Run(): mixed {
		$user = \Sessions::currentUser();
		return ($user['name']??'').' '.($user['surname']??'');
	}

}