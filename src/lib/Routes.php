<?php

class Routes {
	private array $routes = [];

	private static $_instance;

	private function __construct() {}
	private function __clone() {}
	public function __wakeup() {}

	public static function getInstance(): self {
		if (self::$_instance === null) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	public function addRoute($route, $class) {
		$this->routes[$route] = $class;
	}

	public function getRoute($route) {
		return $this->routes[$route];
	}

	public function getRoutes() {
		return $this->routes;
	}

	public function setRoutes($routes) {
		$this->routes = $routes;
	}

	public function toArray() {
		return $this->routes;
	}

	public function __debugInfo() {
		return $this->toArray();
	}

	public function __toString() {
		return json_encode($this->toArray());
	}
}

