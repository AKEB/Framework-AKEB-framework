<?php

error_reporting(E_ALL);

class First_TestCase extends PHPUnit\Framework\TestCase {

	protected function setUp(): void {

	}

	function test_true()
	{
		$this->assertTrue(true);
		// $data = \Users::data();
		// $this->assertTrue(isset($data));
		// $this->assertTrue(is_array($data));
		// $this->assertTrue(count($data) > 0);
	}

}