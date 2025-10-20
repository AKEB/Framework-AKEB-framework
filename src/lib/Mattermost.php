<?php

class Mattermost {
	private ?\Gnello\Mattermost\Driver $driver = null;
	private ?string $bot_id = null;
	private array $options = [];

	public function __construct(?string $url='', ?string $token='', ?string $scheme=null, ?string $basePath=null) {
		$this->options = [
			'url' => '',
			'token' => '',
			'scheme' => 'https',
			'basePath' => '/api/v4',
		];
		if ($url) $this->options['url'] = $url;
		if ($token) $this->options['token'] = $token;
		if ($scheme) $this->options['scheme'] = $scheme;
		if ($basePath) $this->options['basePath'] = $basePath;
	}

	private function initDriver() {
		if ($this->driver) return true;
		$this->bot_id = null;
		$this->driver = null;

		if (!$this->options["url"] && !$this->options["token"]) return false;

		$container = new \Pimple\Container([
			'driver' => [
				'scheme' => $this->options["scheme"],
				'basePath' => $this->options["basePath"],
				'url' => $this->options["url"],
				'token' => $this->options["token"],
			]
		]);
		$this->driver = new \Gnello\Mattermost\Driver($container);
		$result = $this->getDriver()->authenticate();
		if ($result->getStatusCode() != 200 && $result->getStatusCode() != 201) {
			error_log("Mattermost: HTTP ERROR authenticate " . $result->getStatusCode());
			$this->driver = null;
			return false;
		}
		$bot = json_decode($result->getBody(), true);
		if ($bot && $bot['id']) {
			$this->bot_id = $bot['id'];
			return true;
		}
		$this->driver = null;
		$this->bot_id = null;
		return false;
	}

	public function getDriver(): ?\Gnello\Mattermost\Driver {
		if (!$this->driver) {
			$this->initDriver();
		}
		return $this->driver;
	}

	public function getBotId() {
		return $this->bot_id ?: false;
	}

	public function getUserByEmail($email) {
		$result = $this?->getDriver()?->getUserModel()?->getUserByEmail($email);
		if (!$result) return false;
		if ($result?->getStatusCode() == 404) {
			return false;
		}
		if ($result->getStatusCode() != 200 && $result->getStatusCode() != 201) {
			error_log("Mattermost: HTTP ERROR " . $result->getStatusCode() . " " . $result->getReasonPhrase());
			return false;
		}
		$user = json_decode($result->getBody(), true);
		if (!$user) return false;
		if (!is_array($user)) return false;
		return $user;
	}

	public function getUserByUsername($username) {
		$result = $this?->getDriver()?->getUserModel()?->getUserByUsername($username);
		if (!$result) return false;
		if ($result->getStatusCode() == 404) {
			return false;
		}
		if ($result->getStatusCode() != 200 && $result->getStatusCode() != 201) {
			error_log("Mattermost: HTTP ERROR " . $result->getStatusCode() . " " . $result->getReasonPhrase());
			return false;
		}
		$user = json_decode($result->getBody(), true);
		if (!$user) return false;
		if (!is_array($user)) return false;
		return $user;
	}

	public function searchUser($userTerm) {
		$result = $this?->getDriver()?->getUserModel()?->searchUsers(['term' => $userTerm]);
		if (!$result) return false;
		if ($result->getStatusCode() != 200 && $result->getStatusCode() != 201) {
			error_log("Mattermost: HTTP ERROR " . $result->getStatusCode() . " " . $result->getReasonPhrase());
			return false;
		}
		$users = json_decode($result->getBody(), true);
		if (!$users) return false;
		if (!is_array($users)) return false;
		if (count($users) < 1) return false;
		return $users[0];
	}

	public function findUserByEmail(string $userEmail) {
		if (!$userEmail) return false;
		$user = $this->getUserByEmail($userEmail);
		if ($user && is_array($user) && key_exists('id', $user) && strlen($user['id']) > 0) return $user;
		$user_email_name = explode('@', $userEmail);

		$user = $this->getUserByUsername($user_email_name[0]);
		if ($user && is_array($user) && key_exists('id', $user) && strlen($user['id']) > 0) return $user;

		$user = $this->searchUser($userEmail);
		if ($user && is_array($user) && key_exists('id', $user) && strlen($user['id']) > 0) return $user;

		$user = $this->searchUser($user_email_name[0]);
		if ($user && is_array($user) && key_exists('id', $user) && strlen($user['id']) > 0) return $user;

		return false;
	}

	public function sendMessage(string $channel_id, string $message, ?array $props = null, ?string $root_id = null, ?array $file_ids=null) {
		$result = $this?->getDriver()?->getPostModel()?->createPost([
			'channel_id' => $channel_id,
			'message' => $message,
			'root_id' => isset($root_id) && is_string($root_id) ? $root_id : null,
			'props' => isset($props) && is_array($props) ? $props : null,
			'file_ids' => isset($file_ids) && is_array($file_ids) ? $file_ids : null,
		]);
		if (!$result) return false;
		if ($result->getStatusCode() != 200 && $result->getStatusCode() != 201) {
			error_log("Mattermost: HTTP ERROR createPost " . $result->getStatusCode() . ' ' . $result->getBody());
			return false;
		}
		$message = json_decode($result->getBody(), true);
		return $message['id'];
	}

	public function sendMessageByEmail(string $userEmail, string $message, ?array $props = null, ?string $root_id = null) {
		if (!$userEmail) return false;
		$user = $this->findUserByEmail($userEmail);
		if (!$user) {
			error_log("Mattermost: user " . $userEmail . " not found!");
			return false;
		}
		$result = $this?->getDriver()?->getChannelModel()?->createDirectMessageChannel([
			$this->getBotId(),
			$user['id'],
		]);
		if (!$result) return false;
		if ($result->getStatusCode() != 200 && $result->getStatusCode() != 201) {
			error_log("Mattermost: HTTP ERROR createDirectMessageChannel " . $result->getStatusCode() . " " . $result->getReasonPhrase());
			return false;
		}
		$channel = json_decode($result->getBody(), true);

		if (!$channel) return false;
		if (!is_array($channel)) return false;
		if (!$channel['id']) return false;
		return $this->sendMessage($channel['id'], $message, $props, $root_id);
	}
}