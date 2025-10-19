<?php

namespace App;

class Test extends \Routing_Parent implements \Routing_Interface {

	public function Run() {
		$this->check_auth();
		$this->template = new \Template();
		$this->print_header();

		?>
		<h2 class="mt-3">Testing Permissions</h2>
		<?php
		if (\Sessions::checkPermission('worker', 1, READ)) echo "worker 1<br/>\n";
		if (\Sessions::checkPermission('worker', 2, READ)) echo "worker 2<br/>\n";
		if (\Sessions::checkPermission('worker', 3, READ)) echo "worker 3<br/>\n";
		if (\Sessions::checkPermission('worker', 4, READ)) echo "worker 4<br/>\n";
		if (\Sessions::checkPermission('worker', 5, READ)) echo "worker 5<br/>\n";

		?>
		<h2 class="mt-3">Testing WebSocket</h2>
		<button class="btn btn-warning websocket_getUserName">Get user name Button</button>
		<button class="btn btn-warning websocket_test">Test message Button</button>

		<script nonce="<?=\CSP::nonceRandom();?>">
			$(document).ready(function() {
				$('.websocket_getUserName').on('click', function(){
					wss.send('getUserName',{},(response)=>{
						showSuccessToast(response.message, true);
					})
				});
				$('.websocket_test').on('click', function(){
					wss.send('test',null,(response)=>{
						showSuccessToast(response.message, false, 2000);
					})
				});
			});
		</script>
		<?php
	}

	private function print_header() {
		?>
		<div class="float-start"><h1><i class="bi bi-code-square"></i> <?=\T::TestPage();?></h1></div>
		<div class="clearfix"></div>
		<?php
	}
}
