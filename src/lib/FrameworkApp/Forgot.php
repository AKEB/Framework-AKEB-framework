<?php

namespace FrameworkApp;

class Forgot extends \Routing_Parent implements \Routing_Interface {

	public function Run() {
		if (!\Config::getInstance()->app_signin_active) {
			common_redirect('/');
		}

		echo '<h1>Forgot password</h1>';
	}
}
