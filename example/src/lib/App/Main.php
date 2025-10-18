<?php

namespace App;

class Main extends \Routing_Parent implements \Routing_Interface {

	public function Run() {
		$this->check_auth();
		$template = new \Template();
		echo '<h1>'.\T::MainPage().'</h1>';
		// echo 'IP: ' . \Sessions::client_ip();

		if (\Config::getInstance()->telegram_bot_token && \Config::getInstance()->telegram_channel_id && \Config::getInstance()->telegram_thread_id) {
			$bot = new \TelegramBot\Api\BotApi(\Config::getInstance()->telegram_bot_token);
			$bot->sendMessage(\Config::getInstance()->telegram_channel_id, "Test Message ".date('Y-m-d H:i:s'), null, false, null, null, false, \Config::getInstance()->telegram_thread_id);
		}

		// if ($_ENV['TELEGRAM_BOT_TOKEN'] && $_ENV['TELEGRAM_CHANNEL_ID'] && $_ENV['TELEGRAM_THREAD_ID']) {
		// 	$bot = new \TelegramBot\Api\BotApi($_ENV['TELEGRAM_BOT_TOKEN']);
		// 	$bot->sendMessage($_ENV['TELEGRAM_CHANNEL_ID'], "Test Message", null, false, null, null, false, $_ENV['TELEGRAM_THREAD_ID']);
		// }
	}

}
