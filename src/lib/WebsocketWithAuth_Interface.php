<?php

interface WebsocketWithAuth_Interface {
	public function auth_check(?string $session_uid=null): bool;
}