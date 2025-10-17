<?php

error_reporting(E_ALL);

class First_CaseTest extends PHPUnit\Framework\TestCase {

	protected function setUp(): void {

	}

	function test_true() {
		$this->assertTrue(true);
		$this->assertFalse(false);
	}

	function test_admin_user() {
		$data = \Users::data(false, ' ORDER BY id ASC');
		$this->assertTrue(isset($data));
		$this->assertIsArray($data);
		$this->assertGreaterThanOrEqual(1, count($data));
		$admin = $data[0];
		$this->assertTrue(isset($admin));
		$this->assertIsArray($admin);

		$this->assertArrayHasKey('id', $admin);
		$this->assertArrayHasKey('name', $admin);
		$this->assertArrayHasKey('surname', $admin);
		$this->assertArrayHasKey('email', $admin);
		$this->assertArrayHasKey('password', $admin);
		$this->assertArrayHasKey('status', $admin);
		$this->assertArrayHasKey('flags', $admin);
		$this->assertArrayHasKey('registerTime', $admin);

		$this->assertEquals(1, $admin['id']);
		$this->assertEquals('admin', $admin['name']);
		$this->assertEquals('admin', $admin['surname']);
		$this->assertEquals('admin@admin.com', $admin['email']);
		$this->assertNotEmpty($admin['password']);

		$this->assertEquals(\Users::password_hash('Admin@123'), $admin['password']);
		$this->assertTrue(\Users::password_verify('Admin@123', $admin['password']));
		$this->assertFalse(\Users::password_verify('WrongPassword@123', $admin['password']));
		$this->assertFalse(\Users::password_verify('', $admin['password']));
		$this->assertFalse(\Users::password_verify('', ''));
		$this->assertEquals(\Users::password_hash(''), '');

		$this->assertEquals(1, $admin['status']);
		$this->assertEquals(1, $admin['flags']);
		$this->assertTrue($admin['registerTime'] > 0);
	}

	protected function tearDown(): void {
	}

}