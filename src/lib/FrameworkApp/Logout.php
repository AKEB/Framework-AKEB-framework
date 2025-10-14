<?php

namespace FrameworkApp;

class Logout extends \Routing_Parent implements \Routing_Interface {

	public function Run() {
		$this->check_auth();

		if (\Sessions::is_impersonate_user()) {
			// Stop impersonating
			$log_id = \Logs::log('Stop_impersonate_user',\Logs::ACTION_STOP_IMPERSONATE,'user', \Sessions::currentUserId(),[
				'ip' => \Sessions::client_ip(),
			]);
			\Logs::add_tag($log_id, \Users::LOGS_OBJECT, \Sessions::currentUserId());
			\Sessions::stop_impersonate_user();
			common_redirect('/admin/users/');
		} else {
			\Logs::log('Logout',\Logs::ACTION_LOGOUT,'user', \Sessions::currentUserId(),[
				'ip' => \Sessions::client_ip(),
			]);
			\Sessions::clear_session();
			common_redirect('/login/');
		}

	}
}
