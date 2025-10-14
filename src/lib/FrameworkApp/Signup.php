<?php

namespace FrameworkApp;

class Signup extends \Routing_Parent implements \Routing_Interface {

	public function Run() {
		if (!\Config::getInstance()->app_signup_active) {
			common_redirect('/');
		}

		echo '<h1>SignUp</h1>';
	}
}
