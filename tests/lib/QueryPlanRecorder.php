<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use Doctrine\DBAL\Logging\SQLLogger;
use OC\DB\ConnectionAdapter;
use OCP\IDBConnection;
use OCP\Server;

/**
 * Records each distinct query of a test run, with the parameters of its first
 * execution, for tests/query-plan-analyzer.php.
 * Enable by setting TEST_LOG_QUERIES to the log file path.
 */
final class QueryPlanRecorder implements SQLLogger {
	private const RUN_MARKER = 'TEST_LOG_QUERIES_RUN';
	private const LARGE_TABLES = [
		'accounts_data', 'authtoken', 'calendarobjects', 'cards', 'comments',
		'file_locks', 'filecache', 'filecache_extended', 'jobs', 'mounts',
		'preferences', 'properties', 'share', 'storages', 'systemtag_object_mapping',
	];

	/** @var array<string, array{sql: string, origin: string, params: string, test: string}> */
	private array $queries = [];

	private function __construct(
		private string $logFile,
	) {
	}

	public static function install(string $logFile): void {
		$connection = Server::get(IDBConnection::class);
		if (!$connection instanceof ConnectionAdapter) {
			return;
		}

		// A test running in a separate process re-runs the bootstrap, so only the
		// process that owns the run may truncate. Children inherit the marker.
		if (getenv(self::RUN_MARKER) === false) {
			putenv(self::RUN_MARKER . '=' . getmypid());
			file_put_contents($logFile, '');
		}

		$recorder = new self($logFile);
		$connection->getInner()->getConfiguration()->setSQLLogger($recorder);
		register_shutdown_function($recorder->flush(...));
	}

	#[\Override]
	public function startQuery($sql, ?array $params = null, ?array $types = null): void {
		if (!preg_match('/^\s*(SELECT|UPDATE|DELETE)\b/i', $sql)) {
			return;
		}
		$origin = self::origin();
		// A test calling the query's class directly tests that class, a separate
		// origin per test would only churn the baseline
		if (isset($this->queries[$sql . "\0" . $origin]) || is_a(strstr($origin, '::', true) ?: $origin, \PHPUnit\Framework\TestCase::class, true)) {
			return;
		}

		try {
			$serialized = serialize([$params ?? [], $types ?? []]);
		} catch (\Throwable) {
			return;
		}
		$this->queries[$sql . "\0" . $origin] = [
			'sql' => $sql,
			'origin' => $origin,
			'params' => base64_encode($serialized),
			'test' => HttpRequestLogger::currentTest(),
		];
	}

	/**
	 * The caller of the class that built the query, e.g. OC\User\Manager::getByEmail
	 * for a query of OC\Config\UserConfig. Tells apart callers of a shared query.
	 */
	private static function origin(): string {
		$owner = null;
		foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 50) as $frame) {
			$class = $frame['class'] ?? '';
			// classless frames are built-ins such as iterator_to_array() consuming a generator
			if ($class === '' || $class === self::class || preg_match('/^(Doctrine|OCP?\\\\DB|OCP?\\\\AppFramework\\\\Db)\\\\/', $class)) {
				continue;
			}
			$owner ??= $class;
			if ($class !== $owner) {
				return $class . '::' . $frame['function'];
			}
		}
		return $owner ?? '(unknown)';
	}

	#[\Override]
	public function stopQuery(): void {
	}

	public function flush(): void {
		$lines = '';
		foreach ($this->queries as $query) {
			$lines .= json_encode($query, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES) . "\n";
		}
		file_put_contents($this->logFile, $lines, FILE_APPEND | LOCK_EX);
	}

	/**
	 * Large tables a plan filters in a way no index can serve: a sequential scan
	 * although seq scans are disabled, or a function around an indexed column,
	 * e.g. lower((indexed)::text). Independent of which index the planner picks,
	 * as all indexes cost the same on the tiny tables of a test run.
	 *
	 * @param array $plan one "Plan" node of PostgreSQL's EXPLAIN (FORMAT JSON)
	 * @param array<string, list<string>> $indexedColumns by table
	 * @return list<string>
	 */
	public static function unindexedFilters(array $plan, string $prefix, array $indexedColumns): array {
		$tables = [];
		$relation = $plan['Relation Name'] ?? '';
		$table = substr($relation, strlen($prefix));
		if (isset($plan['Filter']) && str_starts_with($relation, $prefix) && in_array($table, self::LARGE_TABLES, true)) {
			// string literals could contain a column name
			$filter = preg_replace("/'(?:[^']|'')*'/", "''", $plan['Filter']);
			$wrapped = array_filter(
				$indexedColumns[$table] ?? [],
				static fn (string $column): bool => (bool)preg_match('/\b\w+\(+"?' . preg_quote($column, '/') . '\b/', $filter),
			);
			if ($plan['Node Type'] === 'Seq Scan' || $wrapped !== []) {
				$tables[] = $table;
			}
		}
		foreach ($plan['Plans'] ?? [] as $child) {
			$tables = array_merge($tables, self::unindexedFilters($child, $prefix, $indexedColumns));
		}
		return $tables;
	}

	/** @return list<string> */
	public static function largeTables(): array {
		return self::LARGE_TABLES;
	}
}
