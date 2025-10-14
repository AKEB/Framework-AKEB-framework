<?php

namespace App;

class Main extends \Routing_Parent implements \Routing_Interface {

	public function Run() {
		$this->check_auth();
		$template = new \Template();
		echo '<h1>'.\T::MainPage().'</h1>';
		// echo 'IP: ' . \Sessions::client_ip();
		// var_dump($_SERVER);
	}

}
