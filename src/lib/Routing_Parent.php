<?php

class Routing_Parent implements \Routing_Interface {
	protected string $error;
	protected string $success;

	protected string $requestBody = '';

	protected \Template $template;

	public function check_auth() {
		\Sessions::session_init();
	}

	protected function get_request_body(): array {
		$this->requestBody = '';
		$f = fopen('php://input','r');
		if ($f) {
			$requestBody = '';
			do {
				$d = fread($f,1024);
				$requestBody .= $d;
			} while ($d);
			fclose($f);
			$this->requestBody = $requestBody;
		}
		if (!$this->requestBody) return [];
		$requestBodyArray = @json_decode($this->requestBody, true);
		if ($requestBodyArray && is_array($requestBodyArray)) {
			return $requestBodyArray;
		} else {
			parse_str($this->requestBody, $requestBodyArray);
			if ($requestBodyArray && is_array($requestBodyArray)) {
				return $requestBodyArray;
			}
		}
		return [];
	}

	protected function processRequest() {
		$this->error = '';
		// Обработка GET параметров
		if ($_GET) $this->handleGetData($_GET);
		// Обработка POST параметров
		if ($_POST) $this->handlePostData($_POST);
		// Обработка POST Body данных
		$data = $this->get_request_body();
		if ($data) $this->handleBodyData($data);
	}

	protected function handleGetData(array $data) {
		// Обработка GET данных
		if (!isset($data)) return;
	}

	protected function handlePostData(array $data) {
		// Обработка POST данных
		if (!isset($data)) return;
	}

	protected function handleBodyData(array $data) {
		// Обработка POST Body данных
		if (!isset($data)) return;
	}

	public function __destruct() {
		global $error, $success;
		if (isset($this->error) && $this->error != '') $error = $this->error;
		if (isset($this->success) && $this->success != '') $success = $this->success;
	}

}