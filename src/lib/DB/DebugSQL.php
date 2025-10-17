<?php

namespace DB;

class DebugSQL {
	public string $sql = "";
	public float $time_start = 0;
	public float $time_end = 0;
	public string $error = '';
	public string $backtrace = '';

	public function __destruct() {
		$error_log = '';
		$error_log .= $this->sql.PHP_EOL;
		if ($this->error) {
			$error_log .= $this->error.PHP_EOL;
		}
		if ($this->time_end - $this->time_start > 0.9) {
			$error_log .= "LONG [ ".($this->time_end - $this->time_start)." ]".PHP_EOL;
		}
		$error_log .= $this->backtrace;
		error_log(
			sprintf("[%s] %s", date('Y-m-d H:i:s'), $error_log),
			3,
			"/var/log/php/mysql_debug.log"
		);
	}
}