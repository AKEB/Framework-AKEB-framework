<?php

/**
 * Format/parse UI datetimes in the current user's timezone.
 * Storage remains unix timestamps; alerts/API keep server TZ.
 */
class UserTime {
	private static ?string $timezoneCache = null;
	private static ?\DateTimeZone $zoneCache = null;
	private static string $zoneCacheName = '';

	public static function serverTimezone(): string {
		$tz = strval(\Config::getInstance()->timezone ?? 'UTC');
		return static::isValidTimezone($tz) ? $tz : 'UTC';
	}

	public static function isValidTimezone(string $timezone): bool {
		if ($timezone === '') {
			return false;
		}
		try {
			new \DateTimeZone($timezone);
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public static function timezone(): string {
		if (static::$timezoneCache !== null) {
			return static::$timezoneCache;
		}
		$tz = '';
		if (class_exists('\\Sessions', false) || class_exists('\\Sessions')) {
			$tz = strval(\Sessions::get_server_cookie('timezone', ''));
		}
		if (!static::isValidTimezone($tz)) {
			$tz = static::serverTimezone();
		}
		static::$timezoneCache = $tz;
		return static::$timezoneCache;
	}

	/** Reset cached timezone (e.g. after cookie change in the same request). */
	public static function clearCache(): void {
		static::$timezoneCache = null;
		static::$zoneCache = null;
		static::$zoneCacheName = '';
	}

	public static function setTimezone(string $timezone): bool {
		if (!static::isValidTimezone($timezone)) {
			return false;
		}
		\Sessions::set_server_cookie('timezone', $timezone);
		static::$timezoneCache = $timezone;
		static::$zoneCache = null;
		static::$zoneCacheName = '';
		return true;
	}

	public static function listTimezones(): array {
		$list = \timezone_identifiers_list();
		$out = [];
		foreach ($list as $id) {
			$out[$id] = $id;
		}
		return $out;
	}

	private static function zone(): \DateTimeZone {
		$name = static::timezone();
		if (static::$zoneCache !== null && static::$zoneCacheName === $name) {
			return static::$zoneCache;
		}
		static::$zoneCache = new \DateTimeZone($name);
		static::$zoneCacheName = $name;
		return static::$zoneCache;
	}

	public static function format(int $ts, string $format = 'Y-m-d H:i:s'): string {
		if ($ts <= 0) {
			return '';
		}
		$name = static::timezone();
		// Fast path: matches PHP default timezone (typical after deploy TZ == user TZ).
		if ($name === date_default_timezone_get()) {
			return date($format, $ts);
		}
		try {
			return (new \DateTimeImmutable('@' . $ts))->setTimezone(static::zone())->format($format);
		} catch (\Exception $e) {
			return date($format, $ts);
		}
	}

	/**
	 * Parse a naive datetime/date string in the user timezone to unix timestamp.
	 * Accepts "Y-m-d H:i:s", "Y-m-d H:i", "Y-m-d".
	 */
	public static function parse(string $value): ?int {
		$value = trim($value);
		if ($value === '') {
			return null;
		}
		$formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'];
		$tz = static::zone();
		foreach ($formats as $format) {
			$dt = \DateTimeImmutable::createFromFormat('!' . $format, $value, $tz);
			if ($dt instanceof \DateTimeImmutable) {
				$errors = \DateTimeImmutable::getLastErrors();
				if (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
					continue;
				}
				return $dt->getTimestamp();
			}
		}
		return null;
	}

	public static function startOfDay(int $ts = 0): int {
		if ($ts <= 0) {
			$ts = time();
		}
		$dt = (new \DateTimeImmutable('@' . $ts))->setTimezone(static::zone())->setTime(0, 0, 0);
		return $dt->getTimestamp();
	}

	public static function endOfDay(int $ts = 0): int {
		if ($ts <= 0) {
			$ts = time();
		}
		$dt = (new \DateTimeImmutable('@' . $ts))->setTimezone(static::zone())->setTime(23, 59, 59);
		return $dt->getTimestamp();
	}

	/** Parse Y-m-d and return start of that day in user TZ. */
	public static function parseDateStart(string $date): ?int {
		$ts = static::parse(trim($date));
		return $ts === null ? null : static::startOfDay($ts);
	}

	/** Parse Y-m-d and return end of that day in user TZ. */
	public static function parseDateEnd(string $date): ?int {
		$ts = static::parse(trim($date));
		return $ts === null ? null : static::endOfDay($ts);
	}
}
