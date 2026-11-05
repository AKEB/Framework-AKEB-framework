<?php

/**
 * CSRF tokens for mutating UI actions.
 *
 * Token is derived as HMAC(session_id, server secret) — no shared Cache/Memcached
 * required. That keeps login/forms working across multiple PHP nodes even when
 * file-cache or memcached is missing / not shared.
 */
class Csrf {

	public const FIELD = '_csrf';

	/** @var array<string,string> */
	private static array $tokenBySession = [];

	static public function token(): string {
		$sessionId = \Sessions::get_session_id();
		if ($sessionId === '') {
			return '';
		}
		if (isset(static::$tokenBySession[$sessionId])) {
			return static::$tokenBySession[$sessionId];
		}
		$secret = static::secret();
		if ($secret === '') {
			return '';
		}
		$token = hash_hmac('sha256', 'csrf|'.$sessionId, $secret);
		static::$tokenBySession[$sessionId] = $token;
		return $token;
	}

	static public function validate(?string $token = null): bool {
		$expected = static::token();
		if ($expected === '') {
			return false;
		}
		$got = $token;
		if ($got === null) {
			$got = $_POST[self::FIELD] ?? '';
		}
		return is_string($got) && $got !== '' && hash_equals($expected, $got);
	}

	/** Require POST + valid CSRF token; otherwise 403. */
	static public function requirePost(): void {
		$method = strtoupper(strval($_SERVER['REQUEST_METHOD'] ?? 'GET'));
		if ($method !== 'POST' || !static::validate()) {
			e403();
		}
	}

	/** Hidden input for HTML forms. */
	static public function field(): string {
		$token = htmlspecialchars(static::token(), ENT_QUOTES, 'UTF-8');
		$field = htmlspecialchars(self::FIELD, ENT_QUOTES, 'UTF-8');
		return '<input type="hidden" name="'.$field.'" value="'.$token.'"/>';
	}

	/**
	 * JS statements: create and submit a POST form with CSRF.
	 * $urlExpr — JS expression for the form action (e.g. "'/path/' + id + '/'").
	 */
	static public function jsPostToUrl(string $urlExpr): string {
		$token = json_encode(static::token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		$field = json_encode(self::FIELD, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		return 'var __csrfForm=document.createElement("form");__csrfForm.method="POST";__csrfForm.action=('.$urlExpr.');'
			.'var __csrfInput=document.createElement("input");__csrfInput.type="hidden";__csrfInput.name='.$field.';__csrfInput.value='.$token.';'
			.'__csrfForm.appendChild(__csrfInput);document.body.appendChild(__csrfForm);__csrfForm.submit();';
	}

	/** Server-side secret used to bind the token to the session cookie. */
	static private function secret(): string {
		$salt = strval(\Config::getInstance()->password_salt ?? '');
		if ($salt !== '') {
			return $salt;
		}
		// Keep CSRF working when PASSWORD_SALT is unset (prefer setting it in prod).
		return 'akeb-framework-csrf-v1';
	}
}
