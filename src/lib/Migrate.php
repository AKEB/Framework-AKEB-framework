<?php

class Migrate {
	static private $doneMigrations = [];
	static private $depends = [];

	static private function _migrate(string $migrationName): void {
		if (isset(static::$doneMigrations[$migrationName]) && static::$doneMigrations[$migrationName]) return;

		$className = ucfirst($migrationName);
		if (class_exists($className)) {
			if (property_exists($className, 'previous')) {
				$class_vars = get_class_vars($className);
				$previous_migrations = $class_vars['previous'] ?? [];
				if (!is_array($previous_migrations)) $previous_migrations = [$previous_migrations];
				foreach($previous_migrations as $previous) {
					static::_migrate($previous);
				}
			}
			try {
				\DB\Migrations::start_transaction();
				$className::install();
				\DB\Migrations::save([
					'migration_name' => $migrationName,
					'stime' => time(),
					'_mode' => \DB\Common::CSMODE_INSERT,
				], '', 'migration_name');
				\DB\Migrations::commit_transaction();
				echo $migrationName." install\n";
				static::$doneMigrations[$migrationName] = time();
			} catch (\Throwable $e) {
				\DB\Migrations::rollback_transaction();
				throw $e;
			}
		}
	}

	static private function _rollback(string $migrationName): void {
		if (!static::$doneMigrations[$migrationName]) return;
		if (isset(static::$depends[$migrationName]) && static::$depends[$migrationName]) {
			foreach(static::$depends[$migrationName] as $next) {
				static::_rollback($next);
			}
		}
		$className = ucfirst($migrationName);
		if (class_exists($className)) {
			try {
				\DB\Migrations::start_transaction();
				$className::uninstall();
				\DB\Migrations::delete(['migration_name' => $migrationName],'','migration_name');
				\DB\Migrations::commit_transaction();
				unset(static::$doneMigrations[$migrationName]);
				echo $migrationName." uninstall\n";
			} catch (\Throwable $e) {
				\DB\Migrations::rollback_transaction();
				throw $e;
			}
		}
	}

	static private function applyFiles(array $allFiles): void {
		$migrations = [];
		foreach($allFiles as $file) {
			$fileName = basename($file);
			$migrationName = str_replace('.php','', $fileName);
			if (isset(static::$doneMigrations[$migrationName]) && static::$doneMigrations[$migrationName]) continue;
			include($file);
			$migrations[$migrationName] = $migrationName;
		}

		while($migrations && count($migrations) > 0) {
			foreach($migrations as $migrationName => $item) {
				if (!isset(static::$doneMigrations[$migrationName]) || !static::$doneMigrations[$migrationName]) {
					static::_migrate($migrationName);
				}
				unset($migrations[$migrationName]);
				continue;
			}
		}
	}

	static public function apply(): void {
		static::$doneMigrations = get_hash(\DB\Migrations::data(), 'migration_name', 'stime');

		$allFiles = glob(__DIR__.'/../migrations/framework_migration_*.php');
		static::applyFiles($allFiles);

		$allFiles = glob(constant('SERVER_ROOT').'/migrations/migration_*.php');
		static::applyFiles($allFiles);
	}

	static private function rollbackFiles(array $migrations, array $allFiles): void {
		static::$depends = [];
		foreach($allFiles as $file) {
			$fileName = basename($file);
			$migrationName = str_replace('.php','', $fileName);
			if (!static::$doneMigrations[$migrationName]) continue;
			include($file);
			$className = ucfirst($migrationName);
			if (class_exists($className)) {
				if (property_exists($className, 'previous')) {
					$class_vars = get_class_vars($className);
					$previous_migrations = $class_vars['previous'] ?? [];
					if (!is_array($previous_migrations)) $previous_migrations = [$previous_migrations];
					foreach($previous_migrations as $previous) {
						static::$depends[$previous][] = $migrationName;
					}
				}
			}
		}

		foreach($migrations as $migration) {
			static::_rollback($migration);
		}
	}

	static public function rollback(array $migrations): void {
		static::$doneMigrations = get_hash(\DB\Migrations::data(), 'migration_name', 'stime');

		$allFiles = glob(__DIR__.'/../migrations/framework_migration_*.php');
		static::applyFiles($allFiles);

		$allFiles = glob(constant('SERVER_ROOT').'/migrations/migration_*.php');
		static::rollbackFiles($migrations, $allFiles);
	}
}