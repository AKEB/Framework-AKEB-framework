<?php

class OAuthException extends Exception {
}

class OAuth {
	public string $authorization_endpoint = '';
	public string $token_endpoint = '';
	public string $userinfo_endpoint = '';

	private string $client_id = '';
	private string $client_secret = '';
	public string $redirect_uri = '';
	public string $scopes = '';
	public string $response_type = 'code';

	private string $access_token = '';
	private string $refresh_token = '';
	private string $token_type = '';

	public function __construct(string $client_id, string $client_secret) {
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
	}

	public function authenticate() {
		if (isset($_REQUEST['code']) && isset($_REQUEST['state'])) {
			$code = $_REQUEST['code'];
			$this->requestTokens($code);
			return;
		}
		$this->requestAuthorization();
		return;
	}

	private function requestAuthorization() {
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: " . $this->authorization_endpoint . '?' . common_build_request([
			'client_id' => $this->client_id,
			'scope' => $this->scopes,
			'response_type' => 'code',
			'state' => time(),
			'redirect_uri' => $this->redirect_uri,
		]));
	}

	private function requestTokens($code) {
		$post = [
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $this->redirect_uri,
		];
		$header = [
			"Content-type: application/x-www-form-urlencoded",
			"Content-Length: " . strlen(http_build_query($post))
		];

		$curl = new \AKEB\CurlGet($this->token_endpoint, [], $post, $header);
		$data = $curl->exec();
		if (!$data || $curl->responseErrorNum || !is_array($data) || (isset($data['error']) && $data['error'])) {
			throw new OAuthException('Failed to authorizeToken '.($curl->responseErrorNum) . ' '. $curl->responseError);
		}
		$this->access_token = $data['access_token'];
		$this->refresh_token = $data['refresh_token'];
		$this->scopes = $data['scope'];
		$this->token_type = $data['token_type'];
		return true;
	}

	public function requestUserInfo(): array {
		if (!$this->access_token || !$this->token_type) return [];
		$header = [
			"Authorization: " . $this->token_type . ' ' . $this->access_token,
		];
		$curl = new \AKEB\CurlGet($this->userinfo_endpoint, [], [], $header);
		$data = $curl->exec();
		if (!$data || $curl->responseErrorNum || !is_array($data) || !$data['username'] || !$data['email']) {
			throw new OAuthException('Failed to getUserInfo '.($curl->responseErrorNum) . ' '. $curl->responseError);
		}
		return $data;
	}

	public function getAccessToken(): string {
		return $this->access_token;
	}

}

