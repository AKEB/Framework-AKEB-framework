<?php

error_reporting(E_ALL);

class UserTimeTest extends PHPUnit\Framework\TestCase {

	protected function tearDown(): void {
		\UserTime::clearCache();
	}

	private function forceTimezone(string $timezone): void {
		$ref = new \ReflectionClass(\UserTime::class);
		$prop = $ref->getProperty('timezoneCache');
		$prop->setAccessible(true);
		$prop->setValue(null, $timezone);
	}

	function test_is_valid_timezone(): void {
		$this->assertTrue(\UserTime::isValidTimezone('UTC'));
		$this->assertTrue(\UserTime::isValidTimezone('Europe/Moscow'));
		$this->assertTrue(\UserTime::isValidTimezone('America/New_York'));
		$this->assertFalse(\UserTime::isValidTimezone(''));
		$this->assertFalse(\UserTime::isValidTimezone('Not/AZone'));
	}

	function test_format_parse_roundtrip_moscow(): void {
		$this->forceTimezone('Europe/Moscow');
		$ts = \UserTime::parse('2024-06-15 12:30:45');
		$this->assertNotNull($ts);
		$this->assertSame('2024-06-15 12:30:45', \UserTime::format($ts));
		$this->assertSame('12:30:45', \UserTime::format($ts, 'H:i:s'));
		$this->assertSame('2024-06-15', \UserTime::format($ts, 'Y-m-d'));
	}

	function test_format_parse_roundtrip_utc(): void {
		$this->forceTimezone('UTC');
		$ts = \UserTime::parse('2024-01-01 00:00:00');
		$this->assertNotNull($ts);
		$this->assertSame(1704067200, $ts);
		$this->assertSame('2024-01-01 00:00:00', \UserTime::format($ts));
	}

	function test_same_wall_clock_differs_by_timezone(): void {
		$this->forceTimezone('Europe/Moscow');
		$moscow = \UserTime::parse('2024-06-15 12:00:00');
		$this->forceTimezone('America/New_York');
		$ny = \UserTime::parse('2024-06-15 12:00:00');
		$this->assertNotNull($moscow);
		$this->assertNotNull($ny);
		$this->assertNotSame($moscow, $ny);
		// June: Moscow UTC+3, New York EDT UTC-4 → 7 hours
		$this->assertSame(7 * 3600, abs($moscow - $ny));
	}

	function test_start_and_end_of_day(): void {
		$this->forceTimezone('Asia/Dubai');
		$mid = \UserTime::parse('2024-03-10 15:22:11');
		$this->assertNotNull($mid);
		$start = \UserTime::startOfDay($mid);
		$end = \UserTime::endOfDay($mid);
		$this->assertSame('2024-03-10 00:00:00', \UserTime::format($start));
		$this->assertSame('2024-03-10 23:59:59', \UserTime::format($end));
	}

	function test_parse_date_helpers(): void {
		$this->forceTimezone('Europe/Moscow');
		$start = \UserTime::parseDateStart('2024-11-13');
		$end = \UserTime::parseDateEnd('2024-11-13');
		$this->assertNotNull($start);
		$this->assertNotNull($end);
		$this->assertSame('2024-11-13 00:00:00', \UserTime::format($start));
		$this->assertSame('2024-11-13 23:59:59', \UserTime::format($end));
	}

	function test_parse_invalid_returns_null(): void {
		$this->forceTimezone('UTC');
		$this->assertNull(\UserTime::parse(''));
		$this->assertNull(\UserTime::parse('not-a-date'));
		$this->assertNull(\UserTime::parse('2024-13-40 99:99:99'));
	}

	function test_format_zero_returns_empty(): void {
		$this->forceTimezone('UTC');
		$this->assertSame('', \UserTime::format(0));
		$this->assertSame('', \UserTime::format(-1));
	}

	function test_list_timezones_contains_common_zones(): void {
		$list = \UserTime::listTimezones();
		$this->assertArrayHasKey('UTC', $list);
		$this->assertArrayHasKey('Europe/Moscow', $list);
		$this->assertArrayHasKey('America/New_York', $list);
	}
}
