<?php

interface Websocket_Interface {
	public function __construct(string $action, array $params=[]);
	public function Run(): mixed;

}