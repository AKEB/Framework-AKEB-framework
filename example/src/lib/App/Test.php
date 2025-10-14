<?php

namespace App;

class Test extends \Routing_Parent implements \Routing_Interface {

	public function Run() {
		$this->check_auth();
		$this->template = new \Template();
		$this->print_header();

		if (\Sessions::checkPermission('worker', 1, READ)) echo "worker 1<br/>\n";
		if (\Sessions::checkPermission('worker', 2, READ)) echo "worker 2<br/>\n";
		if (\Sessions::checkPermission('worker', 3, READ)) echo "worker 3<br/>\n";
		if (\Sessions::checkPermission('worker', 4, READ)) echo "worker 4<br/>\n";
		if (\Sessions::checkPermission('worker', 5, READ)) echo "worker 5<br/>\n";

	}

	private function print_header() {
		?>
		<div class="float-start"><h1><i class="bi bi-code-square"></i> <?=\T::TestPage();?></h1></div>
		<div class="clearfix"></div>
		<?php
	}
}
