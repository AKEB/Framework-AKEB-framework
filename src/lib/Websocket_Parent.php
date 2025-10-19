<?php

abstract class Websocket_Parent implements \Websocket_Interface {

	protected string $action = '';
	protected array $params = [];

	public function auth_check(?string $session_uid=null): bool {
		if (!isset($session_uid)) return false;
		if (!$session_uid) return false;
		$auth_status = \Sessions::session_init(true, $session_uid);
		if (!$auth_status) return false;
		return true;
	}

	public function __construct(string $action, array $params=[]) {
		$this->action = $action;
		$this->params = $params;
	}

}