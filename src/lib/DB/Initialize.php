<?php

namespace DB;

class Initialize {
	private \mysqli|false $db;
	private string $host_name = '';
	private string $database_port = '';
	private string $database_user = '';
	private string $database_password = '';
	private string $database_name = '';

	public function __construct(string $host_name, string $database_user, string $database_password, string $database_name, int $database_port=3306) {
		$this->host_name = $host_name;
		$this->database_port = $database_port;
		$this->database_user = $database_user;
		$this->database_password = $database_password;
		$this->database_name = $database_name;
		$this->db = false;
	}

	public function try_connect() {
		try {
			$db = mysqli_init();

			if (!$db) return false;
			if (!$db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10)) return false;
			if (!$db->real_connect(
				$this->host_name,
				$this->database_user,
				$this->database_password,
				null,
				$this->database_port
			)) {
				return false;
			}
			$result = mysqli_query($db, "SELECT 1;");
			if (!$result) {
				return false;
			}

		} catch (\Throwable) {
			return false;
		}
		return true;
	}

	public function init() {
		$this->db = mysqli_init();
		if (!$this->db) {
			error_log("Can't init mysql database");
			exit;
		}
		if (!$this->db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10)) {
			error_log("Can't set connection timeout to mysql");
			exit;
		}

		if (!$this->db->real_connect(
			$this->host_name,
			$this->database_user,
			$this->database_password,
			null,
			$this->database_port
		)) {
			error_log("Can't connect to mysql");
			exit;
		}

		if ($this->create_database($this->database_name)) {
			$this->create_migrate_table();
		}
	}

	public function __destruct() {
		mysqli_close($this->db);
		$this->db = false;
	}

	public function create_database(string $database_name): bool {
		$result = mysqli_query($this->db, "CREATE DATABASE IF NOT EXISTS `{$database_name}`");
		if (!$result) {
			error_log("Can't create database: ". mysqli_error($this->db));
			return false;
		}
		mysqli_select_db($this->db, $database_name);
		return true;
	}

	public function create_migrate_table(): bool {
		$query = "CREATE TABLE IF NOT EXISTS `migrations` (
			`migration_name` varchar(255) NOT NULL,
			`stime` int NOT NULL,
			PRIMARY KEY (`migration_name`)
		)";
		$result = mysqli_query($this->db, $query);
		if (!$result) {
			error_log("Can't create table: ". mysqli_error($this->db));
			return false;
		}
		return true;
	}

}