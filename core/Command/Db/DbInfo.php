<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Db;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use OC\DB\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DbInfo extends Command {

	/** @var list<array{code: string, message: string, guidance?: string}> */
	private array $warnings = [];

	public function __construct(
		private readonly Connection $connection,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('db:info')
			->setDescription('Show database configuration and operational overview')
			->addOption('json', null, InputOption::VALUE_NONE, 'Output in JSON format')
			->addOption(
				'metrics',
				null,
				InputOption::VALUE_NONE,
				'Show cumulative runtime metrics since database startup or statistics reset',
			)
			->addOption(
				'explain',
				null,
				InputOption::VALUE_NONE,
				'Explain why each displayed setting is relevant',
			)
			->addOption(
				'no-warnings',
				null,
				InputOption::VALUE_NONE,
				'Do not show operational warnings',
			);
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->warnings = [];

		$platform = $this->connection->getDatabasePlatform();
		$showDetails = $output->isVerbose();
		$showMetrics = $input->getOption('metrics');
		$showExplanations = $input->getOption('explain');
		$showWarnings = !$input->getOption('no-warnings');

		if ($platform instanceof MySQLPlatform) {
			$info = $this->getMySQLInfo();
		} elseif ($platform instanceof PostgreSQLPlatform) {
			$info = $this->getPostgreSQLInfo();
		} elseif ($platform instanceof SqlitePlatform) {
			$info = $this->getSQLiteInfo();
		} else {
			$output->writeln('<error>Unsupported database platform.</error>');
			return Command::FAILURE;
		}

		if ($showWarnings) {
			$this->collectWarnings($info);
		}

		$displayInfo = $this->getDisplayInfo(
			$info,
			$showDetails,
			$showMetrics,
		);

		if ($input->getOption('json')) {
			$payload = $displayInfo;

			if ($showExplanations) {
				$payload['explanations'] = $this->getExplanations($displayInfo);
			}

			if ($showWarnings) {
				$payload['warnings'] = $this->warnings;
			}

			$output->writeln(json_encode(
				$payload,
				JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR,
			));

			return Command::SUCCESS;
		}

		$table = new Table($output);
		$table->setHeaders(
			$showExplanations
				? ['Setting', 'Value', 'Why it matters']
				: ['Setting', 'Value'],
		);

		foreach ($this->flattenInfo($displayInfo) as $row) {
			$tableRow = [
				$row['setting'],
				$this->formatValue($row['key'], $row['value']),
			];

			if ($showExplanations) {
				$tableRow[] = $this->getExplanation($row['key']);
			}

			$table->addRow($tableRow);
		}

		$table->render();

		if ($showWarnings && $this->warnings !== []) {
			$output->writeln('');
			$output->writeln('<comment>Warnings:</comment>');

			foreach ($this->warnings as $warning) {
				$output->writeln(' - ' . $warning['message']);

				if (isset($warning['guidance'])) {
					$output->writeln(
						'   <comment>Guidance: '
						. $warning['guidance']
						. '</comment>',
					);
				}
			}

			$output->writeln('');
			$output->writeln(
				'<comment>Note: db:info is a lightweight operational overview. '
				. 'Use "occ db:locks" for active blocking, "occ db:size" for per-table storage analysis, '
				. '"occ db:index-usage" for index statistics, and "occ db:schema:check" for schema validation.</comment>',
			);
		}

		return Command::SUCCESS;
	}

	/**
	 * @param array<string, mixed> $info
	 * @return array<string, mixed>
	 */
	private function getDisplayInfo(
		array $info,
		bool $showDetails,
		bool $showMetrics,
	): array {
		$engine = $info['engine'] ?? null;

		$result = [
			'engine' => $engine,
			'server' => $this->selectKeys(
				$this->getArray($info, 'server'),
				[
					'version',
					'database_name',
					'database_time',
					'timezone',
					'uptime_seconds',
					'in_recovery',
				],
			),
			'connection' => $this->selectKeys(
				$this->getArray($info, 'connection'),
				[
					'read_only',
					'transaction_isolation',
					'max_connections',
					'current_connections',
					'active_connections',
				],
			),
		];

		if ($engine === 'mysql') {
			$result['configuration'] = $this->selectKeys(
				$this->getArray($info, 'configuration'),
				[
					'database_charset',
					'database_collation',
				],
			);
			$result['resources'] = $this->selectKeys(
				$this->getArray($info, 'resources'),
				[
					'innodb_buffer_pool_size',
					'max_allowed_packet',
				],
			);
		} elseif ($engine === 'postgresql') {
			$result['configuration'] = $this->selectKeys(
				$this->getArray($info, 'configuration'),
				[
					'fsync',
					'synchronous_commit',
				],
			);
			$result['resources'] = $this->selectKeys(
				$this->getArray($info, 'resources'),
				['shared_buffers'],
			);
			$result['logging'] = $this->selectKeys(
				$this->getArray($info, 'logging'),
				['wal_level'],
			);
		} elseif ($engine === 'sqlite') {
			$result['configuration'] = $this->selectKeys(
				$this->getArray($info, 'configuration'),
				[
					'journal_mode',
					'foreign_keys',
				],
			);
			$result['storage'] = $this->selectKeys(
				$this->getArray($info, 'storage'),
				['database_size'],
			);
		}

		if ($showDetails) {
			$result = [
				'engine' => $engine,
				'server' => $this->getArray($info, 'server'),
				'connection' => $this->getArray($info, 'connection'),
				'configuration' => $this->getArray($info, 'configuration'),
				'resources' => $this->getArray($info, 'resources'),
				'storage' => $this->getArray($info, 'storage'),
				'logging' => $this->getArray($info, 'logging'),
			];
		}

		if ($showMetrics) {
			$result['metrics'] = $this->getArray($info, 'metrics');
		}

		return $this->removeEmptyValues($result);
	}

	/**
	 * @param array<string, mixed> $info
	 */
	private function collectWarnings(array $info): void {
		$engine = $info['engine'] ?? null;
		$readOnly = $info['connection']['read_only'] ?? null;
		$superReadOnly = $info['connection']['super_read_only'] ?? null;

		if ($engine === 'mysql' && ($readOnly === true || $superReadOnly === true)) {
			$this->addWarning(
				'read_only',
				'Database is in read-only mode ('
				. ($superReadOnly ? 'super_read_only' : 'read_only')
				. ' is ON).',
				'This is often intentional on replicas. Verify that this instance is expected to accept Nextcloud writes.',
			);
		}

		if ($engine === 'postgresql' && ($info['server']['in_recovery'] ?? null) === true) {
			$this->addWarning(
				'in_recovery',
				'Database is a standby/replica and is currently read-only (in recovery).',
				'Point Nextcloud at a writable primary database for normal read/write operation.',
			);
		}

		if ($engine === 'sqlite' && $readOnly === true) {
			$this->addWarning(
				'read_only',
				'SQLite is currently in read-only mode.',
				'Verify that the database file and its directory are writable by the web-server user, unless this instance is intentionally read-only.',
			);
		}

		$maxConnections = $info['connection']['max_connections'] ?? null;
		$currentConnections = $info['connection']['current_connections'] ?? null;

		if (is_int($maxConnections)
			&& $maxConnections > 0
			&& is_int($currentConnections)
		) {
			$usage = (int)round(
				($currentConnections / $maxConnections) * 100,
			);

			if ($usage >= 80) {
				$guidance = match ($engine) {
					'postgresql' => 'Check application pooling, persistent worker behavior, and long-running backends before increasing max_connections.',
					'mysql' => 'Check long-lived application connections, pool sizing, and blocked queries before increasing max_connections.',
					default => 'Check application activity and long-running operations.',
				};

				$this->addWarning(
					'connection_pressure',
					sprintf(
						'Connection usage is high (%d of %d, %d%%).',
						$currentConnections,
						$maxConnections,
						$usage,
					),
					$guidance,
				);
			}
		}

		if ($engine === 'mysql') {
			$peak = $info['metrics']['peak_connection_utilization_percent'] ?? null;

			if (is_float($peak) && $peak >= 80) {
				$this->addWarning(
					'peak_connection_pressure',
					sprintf(
						'Peak connection usage reached %.1f%% of max_connections.',
						$peak,
					),
					'This is a peak since startup or status reset. Inspect connection lifetime and concurrent request patterns before changing the limit.',
				);
			}

			$refused = $info['metrics']['connections_refused_at_max_connections'] ?? null;

			if (is_int($refused) && $refused > 0) {
				$this->addWarning(
					'connections_refused',
					sprintf(
						'%d connection attempts were refused because max_connections was reached.',
						$refused,
					),
					'Inspect peak usage, persistent connections, and slow or blocked requests. Raising the limit alone can increase host memory pressure.',
				);
			}

			$lockWaits = $info['metrics']['current_row_lock_waits'] ?? null;

			if (is_int($lockWaits) && $lockWaits > 0) {
				$this->addWarning(
					'row_lock_waits',
					sprintf(
						'%d row lock waits are currently active.',
						$lockWaits,
					),
					'Inspect long-running transactions and contending queries. Run "occ db:locks" to identify the blocking sessions.',
				);
			}
		}

		if ($engine === 'postgresql') {
			if (($info['configuration']['fsync'] ?? null) === false) {
				$this->addWarning(
					'fsync_disabled',
					'fsync is disabled. This risks data loss or corruption after a crash.',
					'This is generally unsafe outside controlled development or testing environments.',
				);
			}

			if (strtolower((string)($info['configuration']['synchronous_commit'] ?? '')) === 'off') {
				$this->addWarning(
					'synchronous_commit_off',
					'synchronous_commit is off. Recently committed transactions may be lost on a crash.',
					'This can reduce commit latency, but acknowledged transactions may not survive an unexpected server failure.',
				);
			}

			$deadlocks = $info['metrics']['deadlocks'] ?? null;

			if (is_int($deadlocks) && $deadlocks > 0) {
				$this->addWarning(
					'deadlocks',
					sprintf(
						'%d deadlocks have been recorded since statistics were reset.',
						$deadlocks,
					),
					'This is cumulative. Investigate recurring increases with PostgreSQL logs and application transaction ordering. Run "occ db:locks" for currently active blocking sessions.',
				);
			}
		}

		foreach ((array)($info['metadata']['query_errors'] ?? []) as $error) {
			$this->addWarning('query_error', (string)$error);
		}
	}

	private function addWarning(
		string $code,
		string $message,
		?string $guidance = null,
	): void {
		$warning = [
			'code' => $code,
			'message' => $message,
		];

		if ($guidance !== null) {
			$warning['guidance'] = $guidance;
		}

		$this->warnings[] = $warning;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getMySQLInfo(): array {
		$errors = [];

		$connection = $this->queryRow(
			'SELECT
				VERSION() AS version,
				DATABASE() AS database_name,
				NOW() AS database_time,
				CURRENT_USER() AS authenticated_user,
				USER() AS client_user',
			$errors,
			'Could not retrieve MySQL server information.',
		);

		$global = $this->getMySQLVariables(
			'GLOBAL',
			[
				'version_comment',
				'character_set_database',
				'collation_database',
				'time_zone',
				'system_time_zone',
				'default_storage_engine',
				'innodb_buffer_pool_size',
				'max_connections',
				'innodb_file_per_table',
				'innodb_default_row_format',
				'innodb_redo_log_capacity',
				'innodb_log_file_size',
				'innodb_log_files_in_group',
				'log_bin',
				'binlog_format',
				'read_only',
				'super_read_only',
				'max_allowed_packet',
			],
			$errors,
		);

		$session = $this->getMySQLVariables(
			'SESSION',
			[
				'time_zone',
				'transaction_isolation',
				'tx_isolation',
				'sql_mode',
			],
			$errors,
		);

		$status = $this->getMySQLStatus(
			[
				'Uptime',
				'Threads_connected',
				'Threads_running',
				'Max_used_connections',
				'Aborted_connects',
				'Aborted_clients',
				'Connection_errors_max_connections',
				'Innodb_buffer_pool_read_requests',
				'Innodb_buffer_pool_reads',
				'Innodb_buffer_pool_pages_dirty',
				'Innodb_buffer_pool_pages_total',
				'Created_tmp_tables',
				'Created_tmp_disk_tables',
				'Innodb_row_lock_current_waits',
				'Innodb_row_lock_waits',
				'Innodb_row_lock_time',
				'Innodb_os_log_written',
			],
			$errors,
		);

		$maxConnections = $this->integerVariable($global, 'max_connections');
		$maxUsedConnections = $this->integerStatus($status, 'Max_used_connections');
		$bufferPoolReadRequests = $this->integerStatus(
			$status,
			'Innodb_buffer_pool_read_requests',
		);
		$bufferPoolReads = $this->integerStatus(
			$status,
			'Innodb_buffer_pool_reads',
		);
		$dirtyPages = $this->integerStatus(
			$status,
			'Innodb_buffer_pool_pages_dirty',
		);
		$totalPages = $this->integerStatus(
			$status,
			'Innodb_buffer_pool_pages_total',
		);
		$tempTables = $this->integerStatus(
			$status,
			'Created_tmp_tables',
		);
		$tempDiskTables = $this->integerStatus(
			$status,
			'Created_tmp_disk_tables',
		);

		$redoLogCapacity = $this->integerVariable(
			$global,
			'innodb_redo_log_capacity',
		);

		if ($redoLogCapacity === null) {
			$logFileSize = $this->integerVariable(
				$global,
				'innodb_log_file_size',
			);
			$logFilesInGroup = $this->integerVariable(
				$global,
				'innodb_log_files_in_group',
			);

			if ($logFileSize !== null && $logFilesInGroup !== null) {
				$redoLogCapacity = $logFileSize * $logFilesInGroup;
			}
		}

		$bufferPoolHitRate = null;

		if ($bufferPoolReadRequests !== null
			&& $bufferPoolReadRequests > 0
			&& $bufferPoolReads !== null
		) {
			$bufferPoolHitRate = round(
				(($bufferPoolReadRequests - $bufferPoolReads)
					/ $bufferPoolReadRequests) * 100,
				4,
			);
		}

		return [
			'engine' => 'mysql',
			'server' => [
				'version' => $connection['version'] ?? null,
				'version_comment' => $this->variable(
					$global,
					'version_comment',
				),
				'database_name' => $connection['database_name'] ?? null,
				'database_time' => $connection['database_time'] ?? null,
				'timezone' => [
					'session' => $this->variable($session, 'time_zone'),
					'global' => $this->variable($global, 'time_zone'),
					'system' => $this->variable($global, 'system_time_zone'),
				],
				'uptime_seconds' => $this->integerStatus($status, 'Uptime'),
			],
			'connection' => [
				'authenticated_user' => $connection['authenticated_user'] ?? null,
				'client_user' => $connection['client_user'] ?? null,
				'read_only' => $this->booleanVariable($global, 'read_only'),
				'super_read_only' => $this->booleanVariable($global, 'super_read_only'),
				'transaction_isolation' => $this->variable(
					$session,
					'transaction_isolation',
				) ?? $this->variable($session, 'tx_isolation'),
				'max_connections' => $maxConnections,
				'current_connections' => $this->integerStatus(
					$status,
					'Threads_connected',
				),
				'active_connections' => $this->integerStatus(
					$status,
					'Threads_running',
				),
			],
			'configuration' => [
				'database_charset' => $this->variable(
					$global,
					'character_set_database',
				),
				'database_collation' => $this->variable(
					$global,
					'collation_database',
				),
				'sql_mode' => $this->variable($session, 'sql_mode'),
				'default_storage_engine' => $this->variable(
					$global,
					'default_storage_engine',
				),
			],
			'resources' => [
				'innodb_buffer_pool_size' => $this->integerVariable(
					$global,
					'innodb_buffer_pool_size',
				),
				'max_allowed_packet' => $this->integerVariable(
					$global,
					'max_allowed_packet',
				),
				'innodb_redo_log_capacity' => $redoLogCapacity,
			],
			'storage' => [
				'innodb_file_per_table' => $this->booleanVariable(
					$global,
					'innodb_file_per_table',
				),
				'innodb_default_row_format' => $this->variable(
					$global,
					'innodb_default_row_format',
				),
			],
			'logging' => [
				'binary_logging' => $this->booleanVariable($global, 'log_bin'),
				'binary_log_format' => $this->variable($global, 'binlog_format'),
			],
			'metrics' => [
				'max_used_connections' => $maxUsedConnections,
				'peak_connection_utilization_percent' => $this->percent(
					$maxUsedConnections,
					$maxConnections,
				),
				'aborted_connections' => $this->integerStatus(
					$status,
					'Aborted_connects',
				),
				'aborted_clients' => $this->integerStatus(
					$status,
					'Aborted_clients',
				),
				'connections_refused_at_max_connections' => $this->integerStatus(
					$status,
					'Connection_errors_max_connections',
				),
				'innodb_buffer_pool_hit_rate_percent' => $bufferPoolHitRate,
				'innodb_dirty_page_ratio_percent' => $this->percent(
					$dirtyPages,
					$totalPages,
				),
				'temporary_disk_table_ratio_percent' => $this->percent(
					$tempDiskTables,
					$tempTables,
				),
				'current_row_lock_waits' => $this->integerStatus(
					$status,
					'Innodb_row_lock_current_waits',
				),
				'row_lock_waits' => $this->integerStatus(
					$status,
					'Innodb_row_lock_waits',
				),
				'row_lock_wait_time_ms' => $this->integerStatus(
					$status,
					'Innodb_row_lock_time',
				),
				'innodb_os_log_written' => $this->integerStatus(
					$status,
					'Innodb_os_log_written',
				),
			],
			'metadata' => [
				'query_errors' => $errors,
			],
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getPostgreSQLInfo(): array {
		$errors = [];

		$info = $this->queryRow(
			"SELECT
				version() AS version,
				current_database() AS database_name,
				CURRENT_TIMESTAMP AS database_time,
				current_setting('TimeZone', true) AS timezone,
				current_user AS current_user,
				session_user AS session_user,
				current_setting('server_version', true) AS server_version,
				current_setting('max_connections', true) AS max_connections,
				current_setting('shared_buffers', true) AS shared_buffers,
				current_setting('work_mem', true) AS work_mem,
				current_setting('maintenance_work_mem', true) AS maintenance_work_mem,
				current_setting('effective_cache_size', true) AS effective_cache_size,
				current_setting('default_transaction_isolation', true) AS transaction_isolation,
				current_setting('default_transaction_read_only', true) AS default_transaction_read_only,
				current_setting('synchronous_commit', true) AS synchronous_commit,
				current_setting('fsync', true) AS fsync,
				current_setting('wal_level', true) AS wal_level,
				current_setting('max_wal_size', true) AS max_wal_size,
				current_setting('checkpoint_completion_target', true) AS checkpoint_completion_target,
				current_setting('lc_collate', true) AS lc_collate,
				current_setting('lc_ctype', true) AS lc_ctype,
				pg_postmaster_start_time() AS server_start_time,
				EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - pg_postmaster_start_time()))::bigint AS uptime_seconds,
				pg_is_in_recovery() AS in_recovery",
			$errors,
			'Could not retrieve PostgreSQL server information.',
		);

		$connections = $this->queryRow(
			"SELECT
				COUNT(*) AS connections,
				COUNT(*) FILTER (WHERE state = 'active') AS active_connections
			FROM pg_stat_activity",
			$errors,
			'Could not retrieve connection counts from pg_stat_activity.',
		);

		$databaseMetrics = $this->queryRow(
			'SELECT
				numbackends,
				xact_commit,
				xact_rollback,
				blks_read,
				blks_hit,
				temp_files,
				temp_bytes,
				deadlocks
			FROM pg_stat_database
			WHERE datname = current_database()',
			$errors,
			'Could not retrieve PostgreSQL database metrics.',
		);

		$blocksHit = $this->integer($databaseMetrics['blks_hit'] ?? null);
		$blocksRead = $this->integer($databaseMetrics['blks_read'] ?? null);
		$inRecovery = $this->boolean($info['in_recovery'] ?? null);

		return [
			'engine' => 'postgresql',
			'server' => [
				'version' => $info['version'] ?? null,
				'server_version' => $info['server_version'] ?? null,
				'database_name' => $info['database_name'] ?? null,
				'database_time' => $this->string($info['database_time'] ?? null),
				'timezone' => $info['timezone'] ?? null,
				'server_start_time' => $this->string(
					$info['server_start_time'] ?? null,
				),
				'uptime_seconds' => $this->integer(
					$info['uptime_seconds'] ?? null,
				),
				'in_recovery' => $inRecovery,
			],
			'connection' => [
				'current_user' => $info['current_user'] ?? null,
				'session_user' => $info['session_user'] ?? null,
				'read_only' => $inRecovery,
				'transaction_isolation' => $info['transaction_isolation'] ?? null,
				'max_connections' => $this->integer(
					$info['max_connections'] ?? null,
				),
				'current_connections' => $this->integer(
					$connections['connections'] ?? null,
				),
				'active_connections' => $this->integer(
					$connections['active_connections'] ?? null,
				),
			],
			'configuration' => [
				'locale_collation' => $info['lc_collate'] ?? null,
				'default_transaction_read_only' => $this->boolean(
					$info['default_transaction_read_only'] ?? null,
				),
				'synchronous_commit' => $info['synchronous_commit'] ?? null,
				'fsync' => $this->boolean($info['fsync'] ?? null),
				'lc_ctype' => $info['lc_ctype'] ?? null,
			],
			'resources' => [
				'shared_buffers' => $info['shared_buffers'] ?? null,
				'work_mem' => $info['work_mem'] ?? null,
				'maintenance_work_mem' => $info['maintenance_work_mem'] ?? null,
				'effective_cache_size' => $info['effective_cache_size'] ?? null,
			],
			'logging' => [
				'wal_level' => $info['wal_level'] ?? null,
				'max_wal_size' => $info['max_wal_size'] ?? null,
				'checkpoint_completion_target' => is_numeric(
					$info['checkpoint_completion_target'] ?? null,
				) ? (float)$info['checkpoint_completion_target'] : null,
			],
			'metrics' => [
				'active_backends' => $this->integer(
					$databaseMetrics['numbackends'] ?? null,
				),
				'database_cache_hit_rate_percent' => $this->percent(
					$blocksHit,
					($blocksHit ?? 0) + ($blocksRead ?? 0),
					4,
				),
				'temporary_files' => $this->integer(
					$databaseMetrics['temp_files'] ?? null,
				),
				'temporary_bytes' => $this->integer(
					$databaseMetrics['temp_bytes'] ?? null,
				),
				'committed_transactions' => $this->integer(
					$databaseMetrics['xact_commit'] ?? null,
				),
				'rolled_back_transactions' => $this->integer(
					$databaseMetrics['xact_rollback'] ?? null,
				),
				'deadlocks' => $this->integer(
					$databaseMetrics['deadlocks'] ?? null,
				),
			],
			'metadata' => [
				'query_errors' => $errors,
			],
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getSQLiteInfo(): array {
		$errors = [];

		$pageSize = $this->sqlitePragma('page_size', $errors);
		$pageCount = $this->sqlitePragma('page_count', $errors);
		$freelistCount = $this->sqlitePragma('freelist_count', $errors);

		$databaseSize = null;

		if (is_numeric($pageSize) && is_numeric($pageCount)) {
			$databaseSize = (int)$pageSize * (int)$pageCount;
		}

		return [
			'engine' => 'sqlite',
			'server' => [
				'version' => $this->sqliteValue('sqlite_version()', $errors),
				'database_name' => 'main',
				'database_time' => $this->sqliteValue(
					"datetime('now')",
					$errors,
				),
				'timezone' => 'UTC',
			],
			'connection' => [
				'read_only' => $this->boolean(
					$this->sqlitePragma('query_only', $errors),
				),
			],
			'configuration' => [
				'database_charset' => $this->sqlitePragma('encoding', $errors),
				'journal_mode' => $this->sqlitePragma('journal_mode', $errors),
				'synchronous' => $this->sqlitePragma('synchronous', $errors),
				'foreign_keys' => $this->boolean(
					$this->sqlitePragma('foreign_keys', $errors),
				),
				'auto_vacuum' => $this->sqlitePragma('auto_vacuum', $errors),
				'locking_mode' => $this->sqlitePragma('locking_mode', $errors),
				'temporary_store' => $this->sqlitePragma('temp_store', $errors),
				'cache_size' => $this->sqlitePragma('cache_size', $errors),
				'memory_mapped_io_size' => $this->sqlitePragma('mmap_size', $errors),
			],
			'storage' => [
				'page_size' => $pageSize,
				'database_size' => $databaseSize,
			],
			'metrics' => [
				'freelist_ratio_percent' => $this->percent(
					$this->integer($freelistCount),
					$this->integer($pageCount),
				),
			],
			'metadata' => [
				'query_errors' => $errors,
			],
		];
	}

	/**
	 * @param array<string, mixed> $info
	 * @return list<array{key: string, setting: string, value: mixed}>
	 */
	private function flattenInfo(array $info, string $prefix = ''): array {
		$rows = [];

		foreach ($info as $key => $value) {
			if ($key === 'query_errors') {
				continue;
			}

			$path = $prefix === ''
				? (string)$key
				: $prefix . '.' . $key;

			if (is_array($value)) {
				$rows = array_merge(
					$rows,
					$this->flattenInfo($value, $path),
				);
				continue;
			}

			$rows[] = [
				'key' => $path,
				'setting' => $this->getSettingLabel($path),
				'value' => $value,
			];
		}

		return $rows;
	}

	/**
	 * @param array<string, mixed> $info
	 * @return array<string, string>
	 */
	private function getExplanations(array $info): array {
		$explanations = [];

		foreach ($this->flattenInfo($info) as $row) {
			$explanations[$row['key']] = $this->getExplanation($row['key']);
		}

		return $explanations;
	}

	private function getExplanation(string $key): string {
		$explanations = [
			'engine' => 'Identifies the database implementation and available diagnostics.',
			'server.version' => 'Database version affects compatibility, supported features, and security fixes.',
			'server.version_comment' => 'Identifies the database distribution or build variant.',
			'server.server_version' => 'Reports the PostgreSQL server version in a concise form.',
			'server.database_name' => 'Confirms which database this Nextcloud instance is using.',
			'server.database_time' => 'Helps identify clock differences between the application and database.',
			'server.timezone' => 'Timezone mismatches can affect timestamps, scheduled jobs, and date-based queries.',
			'server.timezone.session' => 'The current session timezone affects timestamp interpretation.',
			'server.timezone.global' => 'Default timezone for newly created MySQL sessions.',
			'server.timezone.system' => 'Operating-system timezone reported by the MySQL server.',
			'server.server_start_time' => 'Shows when the database server last started.',
			'server.uptime_seconds' => 'Indicates how long cumulative counters have accumulated. Low uptime can make lifetime metrics less representative.',
			'server.in_recovery' => 'A PostgreSQL server in recovery is a standby/replica and cannot accept writes.',

			'connection.read_only' => 'A read-only database cannot accept Nextcloud writes.',
			'connection.super_read_only' => 'Prevents most writes even for privileged MySQL accounts.',
			'connection.transaction_isolation' => 'Affects locking behavior and which transaction changes are visible.',
			'connection.max_connections' => 'Limits simultaneous database connections. Increasing it has memory and workload consequences.',
			'connection.current_connections' => 'Shows currently open connections. Sustained high utilization can cause queueing or rejected connections.',
			'connection.active_connections' => 'Shows connections currently executing work; compare it with total connections to identify idle pooled sessions.',
			'connection.authenticated_user' => 'The MySQL account whose privileges apply to this session.',
			'connection.client_user' => 'The MySQL username and client host supplied when connecting.',
			'connection.current_user' => 'The PostgreSQL role currently used for permission checks.',
			'connection.session_user' => 'The PostgreSQL role that originally established the session.',

			'configuration.database_charset' => 'The database character set must support all stored text and emoji.',
			'configuration.database_collation' => 'Controls MySQL text comparison and sort behavior.',
			'configuration.locale_collation' => 'Controls PostgreSQL text ordering and comparisons.',
			'configuration.sql_mode' => 'MySQL SQL mode changes validation and compatibility behavior.',
			'configuration.default_storage_engine' => 'Determines storage and transaction behavior for newly created tables.',
			'configuration.default_transaction_read_only' => 'Sets the default read-only state for new PostgreSQL transactions.',
			'configuration.fsync' => 'Controls durable writes. Disabling it risks corruption or data loss after a crash.',
			'configuration.synchronous_commit' => 'Controls whether commits wait for durable WAL flushes. Disabling it can lose recent acknowledged commits after a crash.',
			'configuration.lc_ctype' => 'Defines locale-specific character classification for PostgreSQL.',
			'configuration.journal_mode' => 'Controls SQLite concurrency and crash-recovery behavior.',
			'configuration.synchronous' => 'Controls how aggressively SQLite synchronizes changes to stable storage.',
			'configuration.foreign_keys' => 'Enables SQLite foreign-key constraint enforcement.',
			'configuration.auto_vacuum' => 'Controls how SQLite reclaims free pages from the database file.',
			'configuration.locking_mode' => 'Controls SQLite locking behavior for database connections.',
			'configuration.temporary_store' => 'Controls whether SQLite temporary data is stored in memory or files.',
			'configuration.cache_size' => 'Configures the SQLite page-cache size for this connection.',
			'configuration.memory_mapped_io_size' => 'Sets the SQLite file range eligible for memory-mapped I/O.',

			'resources.innodb_buffer_pool_size' => 'Primary cache for InnoDB data and indexes. Assess it with workload, host memory, and cache hit rate.',
			'resources.max_allowed_packet' => 'Limits the size of individual MySQL client/server messages.',
			'resources.innodb_redo_log_capacity' => 'Redo-log capacity affects write bursts and checkpoint frequency.',
			'resources.shared_buffers' => 'PostgreSQL shared-memory cache for frequently accessed data.',
			'resources.work_mem' => 'Memory per PostgreSQL sort or hash operation before temporary files are used; multiple operations can allocate it concurrently.',
			'resources.maintenance_work_mem' => 'Memory available to operations such as VACUUM and index builds.',
			'resources.effective_cache_size' => 'Planner estimate of operating-system and database cache available for reads.',

			'storage.innodb_file_per_table' => 'Controls whether each InnoDB table uses a separate tablespace file.',
			'storage.innodb_default_row_format' => 'Row format affects InnoDB storage layout and index limits.',
			'storage.page_size' => 'SQLite page size determines the database file layout.',
			'storage.database_size' => 'Current logical SQLite database-file size.',

			'logging.binary_logging' => 'Supports MySQL replication and point-in-time recovery.',
			'logging.binary_log_format' => 'Affects MySQL replication semantics.',
			'logging.wal_level' => 'Determines PostgreSQL replication and recovery capabilities.',
			'logging.max_wal_size' => 'Upper WAL size target before checkpoints are forced.',
			'logging.checkpoint_completion_target' => 'Controls how checkpoint writes are spread over time.',

			'metrics.max_used_connections' => 'Highest number of concurrent MySQL connections since startup or status reset.',
			'metrics.peak_connection_utilization_percent' => 'Highest utilization since startup or status reset. A peak near the limit warrants investigation, not automatic tuning.',
			'metrics.aborted_connections' => 'Cumulative failed MySQL connection attempts since startup or status reset.',
			'metrics.aborted_clients' => 'Cumulative MySQL client connections that ended unexpectedly.',
			'metrics.connections_refused_at_max_connections' => 'Cumulative connection attempts rejected at the connection limit. Any increase means requests could not connect.',
			'metrics.innodb_buffer_pool_hit_rate_percent' => 'Percentage of logical InnoDB reads served from memory instead of storage. Interpret it with workload and uptime.',
			'metrics.innodb_dirty_page_ratio_percent' => 'Percentage of buffer-pool pages modified but not yet flushed. It reflects write and flushing activity, not necessarily a fault.',
			'metrics.temporary_disk_table_ratio_percent' => 'Percentage of MySQL temporary tables created on disk. Higher values can indicate expensive sorts or grouping.',
			'metrics.current_row_lock_waits' => 'Transactions currently waiting for InnoDB row locks. This is an immediate contention signal.',
			'metrics.row_lock_waits' => 'Cumulative InnoDB row-lock waits since startup or status reset.',
			'metrics.row_lock_wait_time_ms' => 'Cumulative time spent waiting for InnoDB row locks; look for continued growth rather than a single total.',
			'metrics.innodb_os_log_written' => 'Cumulative bytes written to the InnoDB redo log since startup.',
			'metrics.active_backends' => 'Current number of PostgreSQL backends connected to this database.',
			'metrics.database_cache_hit_rate_percent' => 'Percentage of PostgreSQL block requests served from cache rather than storage.',
			'metrics.temporary_files' => 'Cumulative PostgreSQL temporary files created since statistics reset.',
			'metrics.temporary_bytes' => 'Cumulative PostgreSQL temporary-file bytes since statistics reset. Growth can indicate sorts or hashes spilling to disk.',
			'metrics.committed_transactions' => 'Cumulative committed PostgreSQL transactions since statistics reset.',
			'metrics.rolled_back_transactions' => 'Cumulative rolled-back PostgreSQL transactions since statistics reset.',
			'metrics.deadlocks' => 'Cumulative PostgreSQL deadlocks since statistics reset. Investigate recurring increases.',
			'metrics.freelist_ratio_percent' => 'Percentage of SQLite pages available for reuse. It does not alone justify running VACUUM.',
		];

		return $explanations[$key] ?? 'Additional database diagnostic information.';
	}

	private function getSettingLabel(string $key): string {
		$labels = [
			'engine' => 'Engine',

			'server.version' => 'Version',
			'server.version_comment' => 'Version Comment',
			'server.server_version' => 'Server Version',
			'server.database_name' => 'Database Name',
			'server.database_time' => 'Database Time',
			'server.timezone' => 'Database Timezone',
			'server.timezone.session' => 'Session Timezone',
			'server.timezone.global' => 'Global Timezone',
			'server.timezone.system' => 'System Timezone',
			'server.server_start_time' => 'Server Start Time',
			'server.uptime_seconds' => 'Server Uptime',
			'server.in_recovery' => 'In Recovery',

			'connection.current_user' => 'Current User',
			'connection.session_user' => 'Session User',
			'connection.authenticated_user' => 'Authenticated User',
			'connection.client_user' => 'Client User',
			'connection.read_only' => 'Read Only',
			'connection.super_read_only' => 'Super Read Only',
			'connection.transaction_isolation' => 'Transaction Isolation',
			'connection.max_connections' => 'Max Connections',
			'connection.current_connections' => 'Current Connections',
			'connection.active_connections' => 'Active Connections',

			'configuration.database_charset' => 'Database Character Set',
			'configuration.database_collation' => 'Database Collation',
			'configuration.locale_collation' => 'Locale Collation',
			'configuration.sql_mode' => 'SQL Mode',
			'configuration.default_storage_engine' => 'Default Storage Engine',
			'configuration.default_transaction_read_only' => 'Default Transaction Read Only',
			'configuration.fsync' => 'FSync',
			'configuration.synchronous_commit' => 'Synchronous Commit',
			'configuration.lc_ctype' => 'Locale Character Type',
			'configuration.journal_mode' => 'Journal Mode',
			'configuration.synchronous' => 'Synchronous',
			'configuration.foreign_keys' => 'Foreign Keys',
			'configuration.auto_vacuum' => 'Auto Vacuum',
			'configuration.locking_mode' => 'Locking Mode',
			'configuration.temporary_store' => 'Temporary Store',
			'configuration.cache_size' => 'Cache Size',
			'configuration.memory_mapped_io_size' => 'Memory-Mapped I/O Size',

			'resources.innodb_buffer_pool_size' => 'InnoDB Buffer Pool',
			'resources.max_allowed_packet' => 'Max Allowed Packet',
			'resources.innodb_redo_log_capacity' => 'InnoDB Redo Log Capacity',
			'resources.shared_buffers' => 'Shared Buffers',
			'resources.work_mem' => 'Work Mem',
			'resources.maintenance_work_mem' => 'Maintenance Work Mem',
			'resources.effective_cache_size' => 'Effective Cache Size',

			'storage.innodb_file_per_table' => 'InnoDB File per Table',
			'storage.innodb_default_row_format' => 'InnoDB Default Row Format',
			'storage.page_size' => 'Page Size',
			'storage.database_size' => 'Database Size',

			'logging.binary_logging' => 'Binary Logging',
			'logging.binary_log_format' => 'Binary Log Format',
			'logging.wal_level' => 'WAL Level',
			'logging.max_wal_size' => 'Max WAL Size',
			'logging.checkpoint_completion_target' => 'Checkpoint Completion Target',

			'metrics.max_used_connections' => 'Max Used Connections',
			'metrics.peak_connection_utilization_percent' => 'Peak Connection Utilization',
			'metrics.aborted_connections' => 'Aborted Connections',
			'metrics.aborted_clients' => 'Aborted Clients',
			'metrics.connections_refused_at_max_connections' => 'Connections Refused at Max Connections',
			'metrics.innodb_buffer_pool_hit_rate_percent' => 'InnoDB Buffer Pool Hit Rate',
			'metrics.innodb_dirty_page_ratio_percent' => 'InnoDB Dirty Page Ratio',
			'metrics.temporary_disk_table_ratio_percent' => 'Temporary Disk Table Ratio',
			'metrics.current_row_lock_waits' => 'Current Row Lock Waits',
			'metrics.row_lock_waits' => 'Row Lock Waits',
			'metrics.row_lock_wait_time_ms' => 'Row Lock Wait Time',
			'metrics.innodb_os_log_written' => 'InnoDB Redo Log Bytes Written',
			'metrics.active_backends' => 'Active Backends',
			'metrics.database_cache_hit_rate_percent' => 'Database Cache Hit Rate',
			'metrics.temporary_files' => 'Temporary Files',
			'metrics.temporary_bytes' => 'Temporary Bytes',
			'metrics.committed_transactions' => 'Committed Transactions',
			'metrics.rolled_back_transactions' => 'Rolled Back Transactions',
			'metrics.deadlocks' => 'Deadlocks',
			'metrics.freelist_ratio_percent' => 'Freelist Ratio',
		];

		if (isset($labels[$key])) {
			return $labels[$key];
		}

		$name = substr($key, strrpos($key, '.') + 1);

		return ucwords(str_replace('_', ' ', $name));
	}

	/**
	 * @param array<string, mixed> $values
	 * @param list<string> $keys
	 * @return array<string, mixed>
	 */
	private function selectKeys(array $values, array $keys): array {
		$result = [];

		foreach ($keys as $key) {
			if (array_key_exists($key, $values)) {
				$result[$key] = $values[$key];
			}
		}

		return $result;
	}

	/**
	 * @param array<string, mixed> $info
	 */
	private function getArray(array $info, string $key): array {
		return is_array($info[$key] ?? null) ? $info[$key] : [];
	}

	/**
	 * @param array<string, mixed> $info
	 * @return array<string, mixed>
	 */
	private function removeEmptyValues(array $info): array {
		$result = [];

		foreach ($info as $key => $value) {
			if (is_array($value)) {
				$value = $this->removeEmptyValues($value);

				if ($value === []) {
					continue;
				}
			} elseif ($value === null) {
				continue;
			}

			$result[$key] = $value;
		}

		return $result;
	}

	/**
	 * @param list<string> $errors
	 * @return array<string, mixed>
	 */
	private function queryRow(
		string $sql,
		array &$errors,
		string $errorMessage,
	): array {
		try {
			return $this->connection->executeQuery($sql)->fetchAssociative() ?: [];
		} catch (\Throwable $e) {
			$errors[] = $this->queryError($errorMessage, $e);
			return [];
		}
	}

	/**
	 * @param list<string> $names
	 * @param list<string> $errors
	 * @return array<string, string>
	 */
	private function getMySQLVariables(
		string $scope,
		array $names,
		array &$errors,
	): array {
		$quotedNames = implode(
			', ',
			array_map(
				static fn (string $name): string => "'" . $name . "'",
				$names,
			),
		);

		try {
			$result = $this->connection->executeQuery(
				'SHOW ' . $scope . ' VARIABLES WHERE Variable_name IN (' . $quotedNames . ')',
			);
		} catch (\Throwable $e) {
			$errors[] = $this->queryError(
				'Could not retrieve ' . $scope . ' VARIABLES. Check database-user privileges.',
				$e,
			);
			return [];
		}

		$values = [];

		while (($row = $result->fetchAssociative()) !== false) {
			$name = strtolower((string)($row['Variable_name'] ?? ''));

			if ($name !== '') {
				$values[$name] = (string)($row['Value'] ?? '');
			}
		}

		return $values;
	}

	/**
	 * @param list<string> $names
	 * @param list<string> $errors
	 * @return array<string, string>
	 */
	private function getMySQLStatus(array $names, array &$errors): array {
		$quotedNames = implode(
			', ',
			array_map(
				static fn (string $name): string => "'" . $name . "'",
				$names,
			),
		);

		try {
			$result = $this->connection->executeQuery(
				'SHOW GLOBAL STATUS WHERE Variable_name IN (' . $quotedNames . ')',
			);
		} catch (\Throwable $e) {
			$errors[] = $this->queryError(
				'Could not retrieve GLOBAL STATUS. Check database-user privileges.',
				$e,
			);
			return [];
		}

		$values = [];

		while (($row = $result->fetchAssociative()) !== false) {
			$name = (string)($row['Variable_name'] ?? '');

			if ($name !== '') {
				$values[$name] = (string)($row['Value'] ?? '');
			}
		}

		return $values;
	}

	/**
	 * @param list<string> $errors
	 */
	private function sqliteValue(string $expression, array &$errors): mixed {
		try {
			$value = $this->connection
				->executeQuery('SELECT ' . $expression)
				->fetchOne();

			return $value === false ? null : $value;
		} catch (\Throwable $e) {
			$errors[] = $this->queryError(
				'Could not evaluate "' . $expression . '".',
				$e,
			);
			return null;
		}
	}

	/**
	 * @param list<string> $errors
	 */
	private function sqlitePragma(string $pragma, array &$errors): mixed {
		try {
			$value = $this->connection
				->executeQuery('PRAGMA ' . $pragma)
				->fetchOne();

			return $value === false ? null : $value;
		} catch (\Throwable $e) {
			$errors[] = $this->queryError(
				'Could not read PRAGMA ' . $pragma . '.',
				$e,
			);
			return null;
		}
	}

	/**
	 * @param array<string, string> $values
	 */
	private function variable(array $values, string $name): ?string {
		return $values[strtolower($name)] ?? null;
	}

	/**
	 * @param array<string, string> $values
	 */
	private function integerVariable(array $values, string $name): ?int {
		return $this->integer($this->variable($values, $name));
	}

	/**
	 * @param array<string, string> $values
	 */
	private function booleanVariable(array $values, string $name): ?bool {
		return $this->boolean($this->variable($values, $name));
	}

	/**
	 * @param array<string, string> $values
	 */
	private function integerStatus(array $values, string $name): ?int {
		return $this->integer($values[$name] ?? null);
	}

	private function queryError(string $message, \Throwable $exception): string {
		return $message . ' [' . get_class($exception) . ']';
	}

	private function percent(
		?int $numerator,
		?int $denominator,
		int $precision = 2,
	): ?float {
		if ($numerator === null || $denominator === null || $denominator <= 0) {
			return null;
		}

		return round(($numerator / $denominator) * 100, $precision);
	}

	private function formatValue(string $key, mixed $value): mixed {
		if ($value === null) {
			return 'N/A';
		}

		if (in_array($key, [
			'resources.innodb_buffer_pool_size',
			'resources.max_allowed_packet',
			'resources.innodb_redo_log_capacity',
			'configuration.memory_mapped_io_size',
			'storage.page_size',
			'storage.database_size',
			'metrics.temporary_bytes',
			'metrics.innodb_os_log_written',
		], true) && is_numeric($value)) {
			return $this->formatBytes((int)$value);
		}

		if ($key === 'server.uptime_seconds' && is_numeric($value)) {
			return $this->formatDuration((int)$value)
				. ' (' . $value . ' seconds)';
		}

		if (str_ends_with($key, '_percent') && is_numeric($value)) {
			return $value . '%';
		}

		if (is_bool($value)) {
			return $value ? 'ON' : 'OFF';
		}

		return $value;
	}

	private function formatBytes(int $bytes): string {
		$units = ['bytes', 'KB', 'MB', 'GB', 'TB'];
		$value = $bytes;
		$unit = 0;

		while ($value >= 1024 && $unit < count($units) - 1) {
			$value /= 1024;
			$unit++;
		}

		if ($unit === 0) {
			return $bytes . ' bytes';
		}

		return round($value, 2)
			. ' ' . $units[$unit]
			. ' (' . $bytes . ' bytes)';
	}

	private function formatDuration(int $seconds): string {
		$days = intdiv($seconds, 86400);
		$hours = intdiv($seconds % 86400, 3600);
		$minutes = intdiv($seconds % 3600, 60);
		$seconds %= 60;

		$parts = [];

		if ($days > 0) {
			$parts[] = $days . 'd';
		}

		if ($hours > 0 || $days > 0) {
			$parts[] = $hours . 'h';
		}

		if ($minutes > 0 || $hours > 0 || $days > 0) {
			$parts[] = $minutes . 'm';
		}

		$parts[] = $seconds . 's';

		return implode(' ', $parts);
	}

	private function string(mixed $value): ?string {
		return $value === null || $value === false ? null : (string)$value;
	}

	private function integer(mixed $value): ?int {
		return is_numeric($value) ? (int)$value : null;
	}

	private function boolean(mixed $value): ?bool {
		if (is_bool($value)) {
			return $value;
		}

		if ($value === null || $value === false || $value === '') {
			return null;
		}

		return match (strtolower((string)$value)) {
			'1', 'on', 'true', 'yes', 't' => true,
			'0', 'off', 'false', 'no', 'f' => false,
			default => null,
		};
	}
}
