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
		$data = \Users::data();
		$this->assertTrue(isset($data));
		$this->assertIsArray($data);
		$this->assertEquals(1, count($data));
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
		$this->assertEquals(1, $admin['status']);
		$this->assertTrue($admin['registerTime'] > 0);
	}

	protected function tearDown(): void {
	}

}