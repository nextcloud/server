<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Josh Duffy <joshduff.jd@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB;

use Doctrine\DBAL\TransactionIsolationLevel;
use OC\DB\Middleware\ConnectionActivityNotifier;
use OC\SystemConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Opt-in capability for a bounded Files name-search prepass.
 *
 * This deliberately lives in the internal DB layer.  Files callers receive an
 * IDBConnection only and must not inspect a ConnectionAdapter or connection
 * parameters.  Until the capability checks below succeed, callers use their
 * existing query path.
 *
 * @internal
 */
final class FilesSearchSnapshot {
	private const LIMIT_KEYS = ['statement_seconds', 'lock_seconds', 'total_seconds'];
	private const MIN_STATEMENT_SECONDS = 1;
	private const MAX_STATEMENT_SECONDS = 10;
	private const MIN_LOCK_SECONDS = 1;
	private const MAX_LOCK_SECONDS = 10;
	private const MIN_TOTAL_SECONDS = 2;
	private const MAX_TOTAL_SECONDS = 30;

	private bool $busy = false;
	private ?int $activeDeadline = null;
	private ?int $activeStatementSeconds = null;

	public function __construct(
		private SystemConfig $config,
		private ConnectionFactory $connectionFactory,
	) {
	}

	/**
	 * Run a bounded prepass on an owned, read-only snapshot.
	 *
	 * A null return means that the capability is unavailable before the
	 * optimized body was admitted.  An empty array is a successful body result.
	 * Once admitted, errors intentionally propagate: retrying or falling back
	 * after an optimized body begins could conceal an uncertain effect.
	 *
	 * @param callable(IDBConnection,callable():void,callable():bool,callable(int,string,int,bool):?array):array $body
	 * @return array|null
	 */
	public function run(IDBConnection $shared, callable $body): ?array {
		if ($this->config->getValue('files_search_bounded_name', false) !== true
			|| PHP_INT_SIZE !== 8
			|| $this->busy
			|| $shared->inTransaction()
			|| !($shared instanceof ConnectionAdapter)
			|| $this->config->getValue('dbtype', 'sqlite') !== 'mysql'
			|| $this->config->getValue('dbpersistent', false) !== false
			|| $this->config->getValue('dbreplica', []) !== []
			|| $this->config->getValue('dbsharding', []) !== []) {
			return null;
		}
		$limits = $this->readBoundedNameLimits();
		if ($limits === null) {
			// There is deliberately no numeric production default. The opt-in
			// route is unavailable until an administrator supplies this exact,
			// typed server-side admission record.
			return null;
		}
		$deadline = hrtime(true) + ($limits['total_seconds'] * 1_000_000_000);

		$this->busy = true;
		$owned = null;
		$originalIsolation = null;
		$originalReadOnly = null;
		$originalLimits = null;
		$result = null;
		$primaryFailure = null;
		$unavailable = false;

		try {
			$actual = $shared->getInner();
			$expectedParams = $this->connectionFactory->createConnectionParams('', [], 'mysql');
			if ($actual->getShardDefinition('filecache') !== null
				|| !$this->hasFactoryOwnedActivityNotifier($actual->getParams())
				|| !$this->connectionParamsMatch($actual->getParams(), $expectedParams)
				|| !$this->isSingleNonPersistentTopology($expectedParams)) {
				$unavailable = true;
			} else {
				$owned = $this->connectionFactory->getConnection('mysql', []);
				// Complete the configured primary connection/listener setup before
				// changing the transaction characteristics for this owned session.
				// A session-level timeout cannot bound this first connection
				// negotiation. The monotonic budget is checked as soon as it returns.
				$owned->ensureConnectedToPrimary();
				if (!$this->hasRemainingBudget($deadline)) {
					$unavailable = true;
				} else {
					try {
						// Capturing the prior per-session values is required for exact
						// restoration. Like the initial connection negotiation, that first
						// local session read cannot itself be capped by a value not yet set.
						$originalLimits = $this->readSessionLimits($owned);
						if ($originalLimits === null || !$this->applySessionLimits($owned, $limits)) {
							$unavailable = true;
						} else {
							$this->activeDeadline = $deadline;
							$this->activeStatementSeconds = $limits['statement_seconds'];
						}
					} catch (\Throwable) {
						// This is still before the optimized body. A server which cannot
						// prove and install the requested session limits declines the route.
						$unavailable = true;
					}
					if ($unavailable || !$this->hasRemainingBudget($deadline)) {
						$unavailable = true;
					} else {
						// Version and catalog checks are now covered by max_statement_time.
						if (!$this->hasRemainingBudget($deadline) || !$this->isMariaDb1011($owned) || !$this->hasRemainingBudget($deadline) || $owned->inTransaction()) {
							$unavailable = true;
						} else {
							$table = $expectedParams['tablePrefix'] . 'filecache';
							if (!$this->hasRemainingBudget($deadline) || !$this->hasStorageNameMtimeFileidIndex($owned, $table) || !$this->hasRemainingBudget($deadline)) {
								$unavailable = true;
							} else {
								$this->assertWithinBudget($deadline);
								$originalIsolation = $this->readSessionIsolation($owned);
								$originalReadOnly = $this->readSessionReadOnly($owned);
								$this->assertWithinBudget($deadline);
								$owned->setTransactionIsolation(TransactionIsolationLevel::REPEATABLE_READ);
								$owned->executeStatement('SET SESSION TRANSACTION READ ONLY');
								$owned->beginTransaction();

								// A closed DBAL connection can reconnect lazily. Never allow
								// the Files continuation to silently run in a new snapshot.
								$identity = $this->readSnapshotIdentity($owned);
								$assertActive = function () use ($owned, $identity, $deadline): void {
									$this->assertWithinBudget($deadline);
									if (!$owned->isConnected() || !$owned->inTransaction()) {
										throw new \RuntimeException('Files search snapshot is no longer active');
									}
									$this->armRemainingStatementTimeout($owned);
									if ($this->readSnapshotIdentity($owned) !== $identity) {
										throw new \RuntimeException('Files search snapshot session changed');
									}
									$this->assertWithinBudget($deadline);
								};
								$metadataTable = $expectedParams['tablePrefix'] . 'files_metadata';
								$verifyOneToOne = function () use ($owned, $table, $metadataTable, $assertActive): bool {
									// This query runs only after the caller's coarse read has
									// acquired its snapshot. It is DB-only: Files cannot choose
									// table identifiers; the rank caller declines on false.
									$assertActive();
									$valid = $this->hasOneToOneFileMetadataRelation($owned, $table, $metadataTable);
									$assertActive();
									return $valid;
								};
								$assertActive();
								$recentNameIds = function (int $storage, string $pattern, int $limit, bool $widen) use ($owned, $table, $metadataTable, $expectedParams, $assertActive): ?array {
									$assertActive();
									if ($storage < 1 || $limit < 1 || $limit > 1024) {
										return null;
									}
									$collation = $this->filesNameCollation($expectedParams);
									if ($collation === null
										|| !$this->acquireRecentMetadataRead($owned, $table, $metadataTable, $assertActive)
										|| !$this->hasNonNullBigIntMtime($owned, $table, $assertActive)
										|| !$this->hasOneToOneFileMetadataRelation($owned, $table, $metadataTable)) {
										return null;
									}
									// The tiny joined PK read above has acquired the filecache/metadata
									// metadata locks in this owned RR transaction. Certify catalog facts
									// only after that acquisition, before any storage-range scan.
									$assertActive();
									if (!$this->hasStorageMtimeFileidNameIndex($owned, $table)) {
										return null;
									}
									$assertActive();
									if (!$this->hasSafeStorageMtimeRange($owned, $table, $metadataTable, $storage, $assertActive)) {
										return null;
									}

									$filecache = $owned->quoteIdentifier($table);
									$metadata = $owned->quoteIdentifier($metadataTable);
									$outerLimit = $widen ? 2048 : $limit;
									$statement = $this->executeBoundedQuery($owned,
										'SELECT `recent`.`fileid` FROM ('
										. 'SELECT `file`.`fileid`, `file`.`mtime`, `file`.`name`, `meta`.`file_id` AS `metadata_file_id` FROM ' . $filecache . ' `file` '
										. 'LEFT JOIN ' . $metadata . ' `meta` ON `file`.`fileid` = `meta`.`file_id` '
										. 'WHERE `file`.`storage` = ? ORDER BY `file`.`mtime` DESC, `file`.`fileid` DESC LIMIT 2048'
										. ') `recent` WHERE `recent`.`name` COLLATE ' . $collation . ' LIKE ? '
										. 'ORDER BY `recent`.`mtime` DESC, `recent`.`fileid` DESC LIMIT ' . $outerLimit,
										[$storage, $pattern],
										[IQueryBuilder::PARAM_INT, IQueryBuilder::PARAM_STR],
									);
									try {
										$rows = $statement->fetchAll();
									} finally {
										$statement->free();
									}
									$assertActive();
									if (count($rows) > $outerLimit) {
										throw new \UnexpectedValueException('Files search recent candidate bound violated');
									}
									$ids = [];
									foreach ($rows as $row) {
										if (!is_array($row)) {
											throw new \UnexpectedValueException('Files search recent candidate row is malformed');
										}
										$value = $row['fileid'] ?? null;
										if ((!is_int($value) && !is_string($value)) || !preg_match('/^[1-9][0-9]*$/D', (string)$value)) {
											throw new \UnexpectedValueException('Files search recent candidate id is malformed');
										}
										$id = filter_var($value, FILTER_VALIDATE_INT);
										if ($id === false || isset($ids[$id])) {
											throw new \UnexpectedValueException('Files search recent candidate ids are malformed');
										}
										$ids[$id] = true;
									}
									return $rows;
								};
								$value = $body(new ConnectionAdapter($owned), $assertActive, $verifyOneToOne, $recentNameIds);
								$assertActive();
								if (!is_array($value)) {
									throw new \UnexpectedValueException('Files search snapshot body must return an array');
								}
								$result = $value;
							}
						}
					}
				}
			}
		} catch (\Throwable $e) {
			$primaryFailure = $e;
		}

		$cleanupFailure = $this->cleanup(
			$owned,
			$originalIsolation,
			$originalReadOnly,
			$originalLimits,
		);
		$this->busy = false;
		$this->activeDeadline = null;
		$this->activeStatementSeconds = null;

		if ($cleanupFailure !== null) {
			throw new \RuntimeException(
				'Unable to release the Files search snapshot',
				0,
				$primaryFailure ?? $cleanupFailure,
			);
		}
		if ($primaryFailure !== null) {
			throw $primaryFailure;
		}

		return $unavailable ? null : $result;
	}

	/**
	 * @param array<string,mixed> $params
	 */
	private function isSingleNonPersistentTopology(array $params): bool {
		return empty($params['persistent'])
			&& empty($params['sharding'])
			&& isset($params['primary'], $params['replica'])
			&& is_array($params['replica'])
			&& count($params['replica']) === 1
			&& $params['primary'] === $params['replica'][0];
	}

	/**
	 * ConnectionFactory appends this process-local notifier after creating the
	 * public connection parameter record. It observes activity only; it is not
	 * part of routing, credentials, or the database topology.
	 *
	 * @param array<string,mixed> $params
	 */
	private function hasFactoryOwnedActivityNotifier(array $params): bool {
		return isset($params['activity_notifier'])
			&& $params['activity_notifier'] instanceof ConnectionActivityNotifier;
	}

	/**
	 * @param array<string,mixed> $actual
	 * @param array<string,mixed> $expected
	 */
	private function connectionParamsMatch(array $actual, array $expected): bool {
		unset($actual['activity_notifier']);
		return $actual === $expected;
	}

	/** @return array{statement_seconds:int,lock_seconds:int,total_seconds:int}|null */
	private function readBoundedNameLimits(): ?array {
		$raw = $this->config->getValue('files_search_bounded_name_limits', null);
		if (!is_array($raw)
			|| count($raw) !== count(self::LIMIT_KEYS)
			|| array_diff(array_keys($raw), self::LIMIT_KEYS) !== []
			|| array_diff(self::LIMIT_KEYS, array_keys($raw)) !== []) {
			return null;
		}
		foreach (self::LIMIT_KEYS as $key) {
			if (!is_int($raw[$key])) {
				return null;
			}
		}
		$statement = $raw['statement_seconds'];
		$lock = $raw['lock_seconds'];
		$total = $raw['total_seconds'];
		if ($statement < self::MIN_STATEMENT_SECONDS || $statement > self::MAX_STATEMENT_SECONDS
			|| $lock < self::MIN_LOCK_SECONDS || $lock > self::MAX_LOCK_SECONDS
			|| $total < self::MIN_TOTAL_SECONDS || $total > self::MAX_TOTAL_SECONDS
			|| $lock > $statement || $statement >= $total) {
			return null;
		}
		return [
			'statement_seconds' => $statement,
			'lock_seconds' => $lock,
			'total_seconds' => $total,
		];
	}

	private function hasRemainingBudget(int $deadline): bool {
		return hrtime(true) < $deadline;
	}

	private function assertWithinBudget(int $deadline): void {
		if (!$this->hasRemainingBudget($deadline)) {
			throw new \RuntimeException('Files search snapshot budget exhausted');
		}
	}

	private function currentStatementLimit(): string {
		if ($this->activeDeadline === null || $this->activeStatementSeconds === null) {
			throw new \LogicException('Files search statement budget is not active');
		}
		$remainingNanoseconds = $this->activeDeadline - hrtime(true);
		// A SET STATEMENT decimal below one microsecond is neither useful nor a
		// portable MariaDB contract. Decline by throwing before dispatch instead.
		if ($remainingNanoseconds < 1_000) {
			throw new \RuntimeException('Files search snapshot budget exhausted');
		}
		$microseconds = min(
			intdiv($remainingNanoseconds, 1_000),
			$this->activeStatementSeconds * 1_000_000,
		);
		if ($microseconds < 1) {
			throw new \RuntimeException('Files search snapshot budget exhausted');
		}
		return sprintf('%d.%06d', intdiv($microseconds, 1_000_000), $microseconds % 1_000_000);
	}

	/**
	 * Dispatch one internal SELECT with a cap no larger than its current
	 * remaining monotonic budget. DBAL prepared-parameter support for MariaDB's
	 * SET STATEMENT ... FOR form remains a native-test gate.
	 *
	 * @param list<mixed> $params
	 * @param list<int|string> $types
	 */
	private function executeBoundedQuery(Connection $connection, string $sql, array $params = [], array $types = []): mixed {
		if (strncasecmp(ltrim($sql), 'SELECT', 6) !== 0) {
			throw new \LogicException('Files search bounded query must be a SELECT');
		}
		return $connection->executeQuery(
			'SET STATEMENT max_statement_time = ' . $this->currentStatementLimit() . ' FOR ' . $sql,
			$params,
			$types,
		);
	}

	private function armRemainingStatementTimeout(Connection $connection): void {
		// This is intentionally done immediately before handing the owned
		// connection to the external helper/native continuation. It limits each
		// later statement to the then-remaining window, but cannot turn opaque
		// multi-statement continuation work into a hard HTTP/request cancellation.
		$connection->executeStatement('SET SESSION max_statement_time = ' . $this->currentStatementLimit());
	}

	/** @return array{max_statement_time:string,lock_wait_timeout:int,innodb_lock_wait_timeout:int}|null */
	private function readSessionLimits(Connection $connection): ?array {
		$statement = $connection->executeQuery(
			'SELECT CAST(@@SESSION.max_statement_time AS CHAR) AS max_statement_time, '
			. '@@SESSION.lock_wait_timeout AS lock_wait_timeout, '
			. '@@SESSION.innodb_lock_wait_timeout AS innodb_lock_wait_timeout',
		);
		try {
			$row = $statement->fetchAssociative();
		} finally {
			$statement->free();
		}
		$row = is_array($row) ? $row : [];
		$max = $row['max_statement_time'] ?? null;
		$lock = $row['lock_wait_timeout'] ?? null;
		$innodbLock = $row['innodb_lock_wait_timeout'] ?? null;
		if (($max = $this->canonicalMaxStatementTime($max)) === null
			|| ($lock = $this->sessionNonNegativeInteger($lock)) === null
			|| ($innodbLock = $this->sessionNonNegativeInteger($innodbLock)) === null) {
			return null;
		}
		return [
			'max_statement_time' => $max,
			'lock_wait_timeout' => $lock,
			'innodb_lock_wait_timeout' => $innodbLock,
		];
	}

	private function canonicalMaxStatementTime(mixed $value): ?string {
		if (is_int($value)) {
			$value = (string)$value;
		} elseif (is_float($value)) {
			if (!is_finite($value) || $value < 0) {
				return null;
			}
			$value = sprintf('%.6F', $value);
		}
		if (!is_string($value) || preg_match('/^(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/D', $value) !== 1) {
			return null;
		}
		if (str_contains($value, '.')) {
			$value = rtrim(rtrim($value, '0'), '.');
		}
		return $value === '' ? '0' : $value;
	}

	private function sessionNonNegativeInteger(mixed $value): ?int {
		if ((!is_int($value) && !is_string($value)) || !preg_match('/^(?:0|[1-9][0-9]*)$/D', (string)$value)) {
			return null;
		}
		$integer = filter_var($value, FILTER_VALIDATE_INT);
		return $integer === false || $integer < 0 ? null : $integer;
	}

	/** @param array{statement_seconds:int,lock_seconds:int,total_seconds:int} $limits */
	private function applySessionLimits(Connection $connection, array $limits): bool {
		// The values are validated native integers before this method builds SQL.
		$connection->executeStatement('SET SESSION max_statement_time = ' . $limits['statement_seconds']);
		$connection->executeStatement('SET SESSION lock_wait_timeout = ' . $limits['lock_seconds']);
		$connection->executeStatement('SET SESSION innodb_lock_wait_timeout = ' . $limits['lock_seconds']);
		$actual = $this->readSessionLimits($connection);
		return $actual !== null
			&& $actual['max_statement_time'] === (string)$limits['statement_seconds']
			&& $actual['lock_wait_timeout'] === $limits['lock_seconds']
			&& $actual['innodb_lock_wait_timeout'] === $limits['lock_seconds'];
	}

	/** @param array{max_statement_time:string,lock_wait_timeout:int,innodb_lock_wait_timeout:int} $limits */
	private function restoreSessionLimits(Connection $connection, array $limits): void {
		// These values came from the same live session and were syntax-checked by
		// readSessionLimits(). Cleanup never opens a replacement connection.
		$connection->executeStatement('SET SESSION max_statement_time = ' . $limits['max_statement_time']);
		$connection->executeStatement('SET SESSION lock_wait_timeout = ' . $limits['lock_wait_timeout']);
		$connection->executeStatement('SET SESSION innodb_lock_wait_timeout = ' . $limits['innodb_lock_wait_timeout']);
	}

	private function isMariaDb1011(Connection $connection): bool {
		if ($connection->getDatabaseProvider(true) !== IDBConnection::PLATFORM_MARIADB) {
			return false;
		}
		$statement = $this->executeBoundedQuery($connection, 'SELECT VERSION()');
		try {
			$version = (string)$statement->fetchOne();
		} finally {
			$statement->free();
		}
		return preg_match('/^10\\.11\\.\\d+(?:-|$)/', $version) === 1;
	}

	private function hasStorageNameMtimeFileidIndex(Connection $connection, string $table): bool {
		$statement = $this->executeBoundedQuery($connection,
			'SELECT INDEX_NAME, NON_UNIQUE, INDEX_TYPE, SEQ_IN_INDEX, COLUMN_NAME, SUB_PART, IGNORED '
			. 'FROM INFORMATION_SCHEMA.STATISTICS '
			. 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '
			. 'ORDER BY INDEX_NAME, SEQ_IN_INDEX',
			[$table],
		);
		try {
			$indexes = [];
			foreach ($statement->fetchAll() as $row) {
				$indexes[(string)$row['INDEX_NAME']][] = $row;
			}
		} finally {
			$statement->free();
		}

		return $this->hasExactIndex($indexes, ['storage', 'name', 'mtime', 'fileid'], true);
	}

	private function hasStorageMtimeFileidNameIndex(Connection $connection, string $table): bool {
		$statement = $this->executeBoundedQuery($connection,
			'SELECT INDEX_NAME, NON_UNIQUE, INDEX_TYPE, SEQ_IN_INDEX, COLUMN_NAME, SUB_PART, IGNORED, COLLATION '
			. 'FROM INFORMATION_SCHEMA.STATISTICS '
			. 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '
			. 'ORDER BY INDEX_NAME, SEQ_IN_INDEX',
			[$table],
		);
		try {
			$indexes = [];
			foreach ($statement->fetchAll() as $row) {
				$indexes[(string)$row['INDEX_NAME']][] = $row;
			}
		} finally {
			$statement->free();
		}
		return $this->hasExactIndex(
			$indexes,
			['storage', 'mtime', 'fileid', 'name'],
			true,
			null,
			['A', 'A', 'A', 'A'],
		);
	}

	/**
	 * Return the exact MySQL ILIKE collation used by the configured connection,
	 * or decline the recent route when that equivalence is not certain.
	 *
	 * @param array<string,mixed> $params
	 */
	private function filesNameCollation(array $params): ?string {
		$collation = $params['collation'] ?? (($params['charset'] ?? 'utf8') . '_general_ci');
		// MySqlExpressionBuilder and AdapterMySQL use the same configured
		// collation-or-charset-plus-_general_ci formula for ILIKE.
		// This small route is established only for utf8mb4 general_ci.
		return $collation === 'utf8mb4_general_ci' ? $collation : null;
	}

	/** Acquire only the metadata locks needed for later catalog certification. */
	private function acquireRecentMetadataRead(Connection $connection, string $filecacheTable, string $metadataTable, callable $assertActive): bool {
		$filecache = $connection->quoteIdentifier($filecacheTable);
		$metadata = $connection->quoteIdentifier($metadataTable);
		$assertActive();
		$statement = $this->executeBoundedQuery($connection,
			'SELECT `file`.`fileid`, `meta`.`file_id` AS `metadata_file_id` FROM ' . $filecache . ' `file` '
			. 'LEFT JOIN ' . $metadata . ' `meta` ON `file`.`fileid` = `meta`.`file_id` '
			. 'ORDER BY `file`.`fileid` ASC LIMIT 1',
		);
		try {
			$statement->fetchAssociative();
		} finally {
			$statement->free();
		}
		$assertActive();
		return true;
	}

	private function hasNonNullBigIntMtime(Connection $connection, string $filecacheTable, callable $assertActive): bool {
		$assertActive();
		$column = $this->executeBoundedQuery($connection,
			'SELECT DATA_TYPE, IS_NULLABLE, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS '
			. 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = \'mtime\'',
			[$filecacheTable],
		);
		try {
			$mtime = $column->fetchAssociative();
		} finally {
			$column->free();
		}
		$assertActive();
		return is_array($mtime)
			&& strtolower((string)($mtime['DATA_TYPE'] ?? '')) === 'bigint'
			&& (string)($mtime['IS_NULLABLE'] ?? '') === 'NO'
			&& preg_match('/^bigint(?:\([0-9]+\))?(?: unsigned)?$/', strtolower(trim((string)($mtime['COLUMN_TYPE'] ?? '')))) === 1;
	}

	private function hasSafeStorageMtimeRange(Connection $connection, string $filecacheTable, string $metadataTable, int $storage, callable $assertActive): bool {
		$filecache = $connection->quoteIdentifier($filecacheTable);
		$metadata = $connection->quoteIdentifier($metadataTable);
		$extrema = [];
		foreach (['ASC', 'DESC'] as $direction) {
			$assertActive();
			$statement = $this->executeBoundedQuery($connection,
				'SELECT `file`.`mtime`, `meta`.`file_id` AS `metadata_file_id` FROM ' . $filecache . ' `file` '
				. 'LEFT JOIN ' . $metadata . ' `meta` ON `file`.`fileid` = `meta`.`file_id` '
				. 'WHERE `file`.`storage` = ? ORDER BY `file`.`mtime` ' . $direction . ' LIMIT 1',
				[$storage],
				[IQueryBuilder::PARAM_INT],
			);
			try {
				$value = $statement->fetchOne();
			} finally {
				$statement->free();
			}
			$assertActive();
			if ($value === false || (!is_int($value) && !is_string($value)) || !preg_match('/^-?[0-9]+$/D', (string)$value)) {
				return false;
			}
			$integer = filter_var($value, FILTER_VALIDATE_INT);
			if ($integer === false || $integer < -9007199254740991 || $integer > 9007199254740991) {
				return false;
			}
			$extrema[] = $integer;
		}
		if (count($extrema) !== 2) {
			return false;
		}
		return count($extrema) === 2;
	}

	private function hasOneToOneFileMetadataRelation(Connection $connection, string $filecacheTable, string $metadataTable): bool {
		$tables = $this->executeBoundedQuery($connection,
			'SELECT TABLE_NAME, ENGINE FROM INFORMATION_SCHEMA.TABLES '
			. 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (?, ?)',
			[$filecacheTable, $metadataTable],
		);
		try {
			$engines = [];
			foreach ($tables->fetchAll() as $row) {
				$engines[(string)$row['TABLE_NAME']] = strtoupper((string)$row['ENGINE']);
			}
		} finally {
			$tables->free();
		}
		if (($engines[$filecacheTable] ?? null) !== 'INNODB' || ($engines[$metadataTable] ?? null) !== 'INNODB') {
			return false;
		}
		// The unique metadata key only establishes a one-to-one SQL join when
		// both join columns have the same non-coercing integral representation.
		// For example, a unique VARCHAR metadata key could contain both "1" and
		// "01", which compare equal to a BIGINT fileid in MariaDB.  Do this
		// physical-schema check inside the owned snapshot, after the coarse read.
		if (!$this->hasCompatibleFileIdColumns($connection, $filecacheTable, $metadataTable)) {
			return false;
		}

		$statement = $this->executeBoundedQuery($connection,
			'SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, INDEX_TYPE, SEQ_IN_INDEX, COLUMN_NAME, SUB_PART, IGNORED '
			. 'FROM INFORMATION_SCHEMA.STATISTICS '
			. 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (?, ?) '
			. 'ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX',
			[$filecacheTable, $metadataTable],
		);
		try {
			$indexes = [];
			foreach ($statement->fetchAll() as $row) {
				$indexes[(string)$row['TABLE_NAME']][(string)$row['INDEX_NAME']][] = $row;
			}
		} finally {
			$statement->free();
		}
		return $this->hasExactIndex($indexes[$filecacheTable] ?? [], ['fileid'], false, 'PRIMARY')
			&& $this->hasExactIndex($indexes[$metadataTable] ?? [], ['file_id'], false);
	}

	private function hasCompatibleFileIdColumns(Connection $connection, string $filecacheTable, string $metadataTable): bool {
		$statement = $this->executeBoundedQuery($connection,
			'SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_TYPE '
			. 'FROM INFORMATION_SCHEMA.COLUMNS '
			. 'WHERE TABLE_SCHEMA = DATABASE() '
			. 'AND ((TABLE_NAME = ? AND COLUMN_NAME = \'fileid\') '
			. 'OR (TABLE_NAME = ? AND COLUMN_NAME = \'file_id\'))',
			[$filecacheTable, $metadataTable],
		);
		try {
			$columns = [];
			foreach ($statement->fetchAll() as $row) {
				$columns[(string)$row['TABLE_NAME']] = $row;
			}
		} finally {
			$statement->free();
		}

		$filecache = $columns[$filecacheTable] ?? null;
		$metadata = $columns[$metadataTable] ?? null;
		if (!is_array($filecache) || !is_array($metadata)
			|| (string)($filecache['DATA_TYPE'] ?? '') !== 'bigint'
			|| (string)($metadata['DATA_TYPE'] ?? '') !== 'bigint'
			|| (string)($filecache['IS_NULLABLE'] ?? '') !== 'NO'
			|| (string)($metadata['IS_NULLABLE'] ?? '') !== 'NO') {
			return false;
		}

		$filecacheType = strtolower(trim((string)($filecache['COLUMN_TYPE'] ?? '')));
		$metadataType = strtolower(trim((string)($metadata['COLUMN_TYPE'] ?? '')));
		return preg_match('/^bigint(?:\\([0-9]+\\))?(?: unsigned)?$/', $filecacheType) === 1
			&& $filecacheType === $metadataType;
	}

	/**
	 * @param array<string,list<array<string,mixed>>> $indexes
	 * @param list<string> $columns
	 */
	private function hasExactIndex(array $indexes, array $columns, bool $nonUnique, ?string $requiredName = null, ?array $directions = null): bool {
		foreach ($indexes as $name => $indexColumns) {
			if (($requiredName !== null && $name !== $requiredName) || count($indexColumns) !== count($columns)) {
				continue;
			}
			$matches = true;
			foreach ($indexColumns as $position => $column) {
				if ((int)$column['NON_UNIQUE'] !== ($nonUnique ? 1 : 0)
					|| strtoupper((string)$column['INDEX_TYPE']) !== 'BTREE'
					|| (int)$column['SEQ_IN_INDEX'] !== $position + 1
					|| (string)$column['COLUMN_NAME'] !== $columns[$position]
					|| ($directions !== null && strtoupper((string)($column['COLLATION'] ?? '')) !== $directions[$position])
					|| $column['SUB_PART'] !== null
					|| strtoupper((string)$column['IGNORED']) === 'YES') {
					$matches = false;
					break;
				}
			}
			if ($matches) {
				return true;
			}
		}
		return false;
	}

	private function readSessionIsolation(Connection $connection): string {
		// MariaDB 10.11 exposes the session value as tx_isolation.  The
		// transaction_isolation spelling is only its newer MariaDB 11.1 name.
		$statement = $this->executeBoundedQuery($connection, 'SELECT @@SESSION.tx_isolation');
		try {
			$isolation = strtoupper(str_replace(' ', '-', (string)$statement->fetchOne()));
		} finally {
			$statement->free();
		}
		if (!in_array($isolation, ['READ-UNCOMMITTED', 'READ-COMMITTED', 'REPEATABLE-READ', 'SERIALIZABLE'], true)) {
			throw new \RuntimeException('Unsupported Files search snapshot isolation');
		}
		return $isolation;
	}

	private function readSessionReadOnly(Connection $connection): bool {
		$statement = $this->executeBoundedQuery($connection, 'SELECT @@SESSION.tx_read_only');
		try {
			$value = $statement->fetchOne();
		} finally {
			$statement->free();
		}
		if ($value !== 0 && $value !== 1 && $value !== '0' && $value !== '1') {
			throw new \RuntimeException('Unsupported Files search snapshot read-only state');
		}
		return (bool)$value;
	}

	/** @return array{connection: string, isolation: string, readOnly: string, active: string} */
	private function readSnapshotIdentity(Connection $connection): array {
		$statement = $this->executeBoundedQuery($connection,
			'SELECT CONNECTION_ID() AS snapshot_id, @@SESSION.tx_isolation AS snapshot_isolation, '
			. '@@SESSION.tx_read_only AS snapshot_read_only, @@SESSION.in_transaction AS snapshot_active',
		);
		try {
			$row = $statement->fetchAssociative();
		} finally {
			$statement->free();
		}
		if (!is_array($row)
			|| !preg_match('/^[1-9][0-9]*$/D', (string)($row['snapshot_id'] ?? ''))
			|| (string)($row['snapshot_isolation'] ?? '') !== 'REPEATABLE-READ'
			|| (string)($row['snapshot_read_only'] ?? '') !== '1'
			|| (string)($row['snapshot_active'] ?? '') !== '1') {
			throw new \RuntimeException('Files search snapshot session is not repeatable read-only');
		}
		return [
			'connection' => (string)$row['snapshot_id'],
			'isolation' => (string)$row['snapshot_isolation'],
			'readOnly' => (string)$row['snapshot_read_only'],
			'active' => (string)$row['snapshot_active'],
		];
	}

	private function cleanup(
		?Connection $owned,
		?string $originalIsolation,
		?bool $originalReadOnly,
		?array $originalLimits,
	): ?\Throwable {
		if ($owned === null || !$owned->isConnected()) {
			// Session settings no longer exist once this nonpersistent owned
			// connection is closed. Restoring them must not open a new session.
			return null;
		}

		$failure = null;
		try {
			// beginTransaction() can throw after it has incremented the DBAL nesting
			// state, so this cannot rely on a post-call bookkeeping flag.
			if ($owned->inTransaction()) {
				$owned->rollBack();
			}
		} catch (\Throwable $e) {
			$failure = $e;
		}
		try {
			if ($owned->isConnected() && $originalIsolation !== null) {
				$owned->setTransactionIsolation($this->transactionIsolationLevel($originalIsolation));
			}
		} catch (\Throwable $e) {
			$failure ??= $e;
		}
		try {
			if ($owned->isConnected() && $originalReadOnly !== null) {
				$owned->executeStatement('SET SESSION TRANSACTION ' . ($originalReadOnly ? 'READ ONLY' : 'READ WRITE'));
			}
		} catch (\Throwable $e) {
			$failure ??= $e;
		}
		try {
			if ($owned->isConnected() && $originalLimits !== null) {
				$this->restoreSessionLimits($owned, $originalLimits);
			}
		} catch (\Throwable $e) {
			$failure ??= $e;
		}
		try {
			$owned->close();
		} catch (\Throwable $e) {
			$failure ??= $e;
		}
		return $failure;
	}

	private function transactionIsolationLevel(string $isolation): int {
		return match ($isolation) {
			'READ-UNCOMMITTED' => TransactionIsolationLevel::READ_UNCOMMITTED,
			'READ-COMMITTED' => TransactionIsolationLevel::READ_COMMITTED,
			'REPEATABLE-READ' => TransactionIsolationLevel::REPEATABLE_READ,
			'SERIALIZABLE' => TransactionIsolationLevel::SERIALIZABLE,
			default => throw new \LogicException('Unsupported Files search snapshot isolation'),
		};
	}
}
