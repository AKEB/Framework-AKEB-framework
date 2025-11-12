<?php

class MNGR {
	private int $sleep_time = 0;
	private string $worker_errorlog_prefix = '';
	private int $start_time = 0;

	public function __construct(?int $sleep_time=0, ?string $memory_limit='32M') {
		set_time_limit(0);
		$this->sleep_time = $sleep_time;
		if ($memory_limit) {
			ini_set('memory_limit', $memory_limit);
		}
		$worker_title = strval($_SERVER['argv'][0] ?? 'worker');
		$worker_number = intval($_SERVER['argv'][1] ?? 0);
		$worker_pid = getmypid();
		$this->worker_errorlog_prefix = "$worker_title"."[$worker_number]"."($worker_pid): ";
		error_log($this->worker_errorlog_prefix.": Started");
		$this->start_time = time();
	}

	public function __destruct() {
		$work_time = time() - $this->start_time;
		$sleepTime = max($this->sleep_time - $work_time,0);
		error_log($this->worker_errorlog_prefix.": Finished $work_time sec; sleep " . intval($sleepTime) . " sec");
		echo "sleep " . intval($sleepTime);
	}

}