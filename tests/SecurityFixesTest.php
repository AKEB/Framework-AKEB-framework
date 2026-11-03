<?php

error_reporting(E_ALL);

class SecurityFixesTest extends PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		if (!defined('SERVER_VERSION')) {
			define('SERVER_VERSION', 'test');
		}
	}

	function test_csrf_api() {
		$this->assertSame('_csrf', Csrf::FIELD);
		$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
		$_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'phpunit';
		\Cache::$file_cache_enable = true;
		$js = Csrf::jsPostToUrl("'/admin/test/' + id + '/'");
		$this->assertStringContainsString('method="POST"', $js);
		$this->assertStringContainsString('_csrf', $js);
		$this->assertStringContainsString("'/admin/test/' + id + '/'", $js);
		$field = Csrf::field();
		$this->assertStringContainsString('name="_csrf"', $field);
		$this->assertStringContainsString('type="hidden"', $field);
		$token = Csrf::token();
		$this->assertGreaterThanOrEqual(32, strlen($token));
		$this->assertTrue(Csrf::validate($token));
		$this->assertFalse(Csrf::validate('not-the-token'));
	}

	function test_login_skips_csrf_for_oauth_callback() {
		$login = new class extends \FrameworkApp\Login {
			public function exposedShouldValidateCsrf(): bool {
				return $this->shouldValidateCsrf();
			}
		};
		$_GET = [];
		$_POST = [];
		$this->assertTrue($login->exposedShouldValidateCsrf());

		$_GET = ['oauth' => 'true', 'code' => 'abc', 'state' => 'xyz'];
		$this->assertFalse($login->exposedShouldValidateCsrf());

		$_GET = [];
		$_POST = ['oidc' => 'true', 'code' => 'abc', 'state' => 'xyz'];
		$this->assertFalse($login->exposedShouldValidateCsrf());

		$_GET = [];
		$_POST = [];
	}

	function test_sessions_cookie_options_and_expire() {
		$opts = \Sessions::cookie_options(time() + 60);
		$this->assertSame('/', $opts['path']);
		$this->assertTrue($opts['httponly']);
		$this->assertSame('Lax', $opts['samesite']);
		$this->assertArrayHasKey('secure', $opts);
		$this->assertArrayHasKey('expires', $opts);
	}

	function test_password_legacy_and_modern() {
		$salt = strval(\Config::getInstance()->password_salt);
		$legacy = md5('Admin@123' . $salt);
		$this->assertFalse(\Users::is_modern_password_hash($legacy));
		$this->assertTrue(\Users::password_needs_rehash($legacy));
		$this->assertTrue(\Users::password_verify('Admin@123', $legacy));
		$this->assertFalse(\Users::password_verify('WrongPassword@123', $legacy));

		$modern = \Users::password_hash('Admin@123');
		$this->assertTrue(\Users::is_modern_password_hash($modern));
		$this->assertTrue(\Users::password_verify('Admin@123', $modern));
		$this->assertFalse(\Users::password_verify('nope', $modern));
		// Strict compare: magic hashes must not match via ==
		$this->assertFalse(\Users::password_verify('x', '0e123'));
	}

	function test_common_is_local_path() {
		$this->assertTrue(common_is_local_path('/admin/users/'));
		$this->assertTrue(common_is_local_path('/'));
		$this->assertFalse(common_is_local_path('//evil.example/admin/'));
		$this->assertFalse(common_is_local_path('https://evil.example/'));
		$this->assertFalse(common_is_local_path(''));
		$this->assertFalse(common_is_local_path(null));
	}

	function test_hide_password_short_and_long() {
		$config = \Config::getInstance();
		$this->assertSame('', $config->_hide_password(''));
		$this->assertSame('', $config->_hide_password(null));
		$this->assertSame('*', $config->_hide_password('a'));
		$this->assertSame('****', $config->_hide_password('abcd'));
		$masked = $config->_hide_password('abcdefghijklmnop');
		$this->assertStringContainsString('*', $masked);
		$this->assertNotSame('abcdefghijklmnop', $masked);
		$this->assertDoesNotMatchRegularExpression('/abcdefghijklmnop/', $masked);
	}

	function test_csp_nonce_is_base64() {
		$nonce = \CSP::nonceRandom();
		$this->assertNotEmpty($nonce);
		$this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/]+=*$/', $nonce);
		$this->assertSame($nonce, \CSP::nonceRandom());
	}

	function test_template_html_input_escapes() {
		ob_start();
		$tpl = new \Template('test', false);
		ob_end_clean();
		$html = $tpl->html_input('x', '"><script>alert(1)</script>', 'Title', false);
		$this->assertStringNotContainsString('<script>', $html);
		$this->assertStringContainsString('&lt;script&gt;', $html);
	}

	function test_template_html_select_escapes() {
		ob_start();
		$tpl = new \Template('test', false);
		ob_end_clean();
		$html = $tpl->html_select('s', ['1' => '<b>x</b>'], '1', 'T', false);
		$this->assertStringNotContainsString('<b>x</b>', $html);
		$this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $html);
	}

	function test_logs_mask_tokens() {
		$formatted = \Logs::format_json_data([
			'password' => 'secretpass',
			'reset_token' => 'f3c1a9d2e8b7c6d5e4f3a2b1c0d9e8f7',
			'email_verification_token' => 'deadbeefdeadbeefdeadbeefdeadbeef',
			'title' => 'ok',
		]);
		$this->assertStringNotContainsString('f3c1a9d2e8b7c6d5e4f3a2b1c0d9e8f7', $formatted);
		$this->assertStringNotContainsString('deadbeefdeadbeefdeadbeefdeadbeef', $formatted);
		$this->assertStringNotContainsString('secretpass', $formatted);
		$this->assertStringContainsString('title', $formatted);
		$this->assertStringContainsString('ok', $formatted);
	}
}
