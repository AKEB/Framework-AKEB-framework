<?php

namespace App;

class Main extends \Routing_Parent implements \Routing_Interface {

	public function Run() {
		$this->check_auth();
		$template = new \Template();
		echo '<h1>'.\T::MainPage().'</h1>';
		// echo 'IP: ' . \Sessions::client_ip();
		?>

		<button class="btn btn-warning websocket_getUserName">getUserName Button</button>
		<button class="btn btn-warning websocket_test">test Button</button>

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

}
