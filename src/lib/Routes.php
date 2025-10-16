<?php

class Routes {

	static private array $routes = [];

	static public function addRoute(string $route, string $className): void {
		static::$routes[$route] = $className;
	}

	static public function getRoute(string $route): string {
		return static::$routes[$route]??'';
	}

	static public function getRoutes(): array {
		return static::$routes??[];
	}

	static public function toArray(): array {
		return static::$routes??[];
	}

}

