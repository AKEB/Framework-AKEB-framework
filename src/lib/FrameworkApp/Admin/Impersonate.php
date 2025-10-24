<?php

namespace FrameworkApp\Admin;

class Impersonate extends \Routing_Parent implements \Routing_Interface {
	private bool $can_impersonate = false;
	private int $user_id = 0;


	public function Run($user_id=null) {
		$this->user_id = intval($user_id ?? 0);
		$this->check_auth();
		$this->check_permissions();

		\Sessions::start_impersonate_user($this->user_id);
		$log_id = \Logs::log('Start_impersonate_user',\Logs::ACTION_START_IMPERSONATE,\Users::LOGS_OBJECT, \Sessions::currentUserId(),[
			'ip' => \Sessions::client_ip(),
		]);
		\Logs::add_tag($log_id, \Users::LOGS_OBJECT, \Sessions::originalUserId());
		common_redirect('/');
	}

	private function check_permissions() {
		\Sessions::requestPermission(\Permissions::ADMIN, 0, READ);

		$this->can_impersonate = \Sessions::checkPermission(\Users::PERMISSION_IMPERSONATE_USER, $this->user_id, READ);

		if (!$this->can_impersonate || !$this->user_id) {
			e403();
		}
	}
}