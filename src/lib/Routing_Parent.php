<?php

class Routing_Parent implements \Routing_Interface {
	protected string $error;
	protected string $success;
	protected \Template $template;

	public function check_auth() {
		\Sessions::session_init();
	}

	protected function processRequest() {
		$this->error = '';
		// Обработка GET параметров
		$this->handleGetData($_GET);
		// Обработка POST параметров
		$this->handlePostData($_POST);
	}

	protected function handleGetData(array $data) {
		// Обработка GET данных
		if (!isset($data)) return;
	}

	protected function handlePostData(array $data) {
		// Обработка POST данных
		if (!isset($data)) return;
	}

	public function __destruct() {
		global $error, $success;
		if (isset($this->error) && $this->error != '') $error = $this->error;
		if (isset($this->success) && $this->success != '') $success = $this->success;
	}

}