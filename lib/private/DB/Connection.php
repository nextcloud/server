<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Statement;
use OC\DB\QueryBuilder\QueryBuilder;
use OC\SystemConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Diagnostics\IEventLogger;
use OCP\Diagnostics\IQueryLogger;
use OCP\IDBConnection;
use OCP\IRequestId;
use OCP\PreConditionNotMetException;
use OCP\Profiler\IProfiler;
use OCP\Security\ISecureRandom;
use OCP\Server;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use function count;
use function in_array;

class Connection extends PrimaryReadReplicaConnection {
	use TDoctrineParameterTypeMap;

	/** @var string */
	protected $tablePrefix;

	/** @var \OC\DB\Adapter $adapter */
	protected $adapter;

	/** @var SystemConfig */
	private $systemConfig;

	private ClockInterface $clock;

	private LoggerInterface $logger;

	protected $lockedTable = null;

	/** @var int */
	protected $queriesBuilt = 0;

	/** @var int */
	protected $queriesExecuted = 0;

	/** @var DbDataCollector|null */
	protected $dbDataCollector = null;
	private array $lastConnectionCheck = [];

	protected ?float $transactionActiveSince = null;

	/** @var array<string, int> */
	protected $tableDirtyWrites = [];

	protected bool $logRequestId;
	protected string $requestId;

	/**
	 * Initializes a new instance of the Connection class.
	 *
	 * @throws \Exception
	 */
	public function __construct(
		private array $params,
		Driver $driver,
		?Configuration $config = null,
	) {
		if (!isset($params['adapter'])) {
			throw new \Exception('adapter not set');
		}
		if (!isset($params['tablePrefix'])) {
			throw new \Exception('tablePrefix not set');
		}
		/**
		 * @psalm-suppress InternalMethod
		 */
		parent::__construct($params, $driver, $config);
		$this->adapter = new $params['adapter']($this);
		$this->tablePrefix = $params['tablePrefix'];

		$this->systemConfig = \OC::$server->getSystemConfig();
		$this->clock = Server::get(ClockInterface::class);
		$this->logger = Server::get(LoggerInterface::class);

		$this->logRequestId = $this->systemConfig->getValue('db.log_request_id', false);
		$this->requestId = Server::get(IRequestId::class)->getId();

		/** @var IProfiler */
		$profiler = Server::get(IProfiler::class);
		if ($profiler->isEnabled()) {
			$this->dbDataCollector = new DbDataCollector(Server::get(IQueryLogger::class));
			$profiler->add($this->dbDataCollector);
		}

		$this->setNestTransactionsWithSavepoints(true);
	}

	/**
	 * @throws Exception
	 */
	public function connect(?string $connectionName = null): Driver\Connection {
		try {
			if ($this->_conn) {
				$this->reconnectIfNeeded();
				/** @psalm-suppress InternalMethod */
				return parent::connect();
			}

			$this->lastConnectionCheck[$this->getConnectionName()] = time();

			// Only trigger the event logger for the initial connect call
			$eventLogger = Server::get(IEventLogger::class);
			$eventLogger->start('connect:db', 'db connection opened');
			/** @psalm-suppress InternalMethod */
			$status = parent::connect();
			$eventLogger->end('connect:db');

			return $status;
		} catch (Exception $e) {
			// throw a new exception to prevent leaking info from the stacktrace
			throw new ConnectionException('Failed to connect to the database: ' . $e->getMessage(), $e->getCode());
		}
	}

	protected function performConnect(?string $connectionName = null): Driver\Connection {
		if (($connectionName ?? 'replica') === 'replica'
			&& count($this->params['replica']) === 1
			&& $this->params['primary'] === $this->params['replica'][0]) {
			return parent::performConnect('primary');
		}
		return parent::performConnect($connectionName);
	}

	public function getStats(): array {
		return [
			'built' => $this->queriesBuilt,
			'executed' => $this->queriesExecuted,
		];
	}

	/**
	 * Returns a QueryBuilder for the connection.
	 */
	public function getQueryBuilder(): IQueryBuilder {
		$this->queriesBuilt++;
		return new QueryBuilder(
			new ConnectionAdapter($this),
			$this->systemConfig,
			$this->logger
		);
	}

	/**
	 * Gets the QueryBuilder for the connection.
	 *
	 * @return \Doctrine\DBAL\Query\QueryBuilder
	 * @deprecated please use $this->getQueryBuilder() instead
	 */
	public function createQueryBuilder(): \Doctrine\DBAL\Query\QueryBuilder {
		$backtrace = $this->getCallerBacktrace();
		$this->logger->debug('Doctrine QueryBuilder retrieved in {backtrace}', ['app' => 'core', 'backtrace' => $backtrace]);
		$this->queriesBuilt++;
		return parent::createQueryBuilder();
	}

	/**
	 * Get the file and line that called the method where `getCallerBacktrace()` was used
	 *
	 * @return string
	 */
	protected function getCallerBacktrace() {
		$traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

		// 0 is the method where we use `getCallerBacktrace`
		// 1 is the target method which uses the method we want to log
		if (isset($traces[1])) {
			return $traces[1]['file'] . ':' . $traces[1]['line'];
		}

		return '';
	}

	/**
	 * @return string
	 */
	public function getPrefix() {
		return $this->tablePrefix;
	}

	/**
	 * Prepares an SQL statement.
	 *
	 * @param string $statement The SQL statement to prepare.
	 * @param int|null $limit
	 * @param int|null $offset
	 *
	 * @return Statement The prepared statement.
	 * @throws Exception
	 */
	public function prepare($sql, $limit = null, $offset = null): Statement {
		if ($limit === -1 || $limit === null) {
			$limit = null;
		} else {
			$limit = (int) $limit;
		}
		if ($offset !== null) {
			$offset = (int) $offset;
		}
		if (!is_null($limit)) {
			$platform = $this->getDatabasePlatform();
			$sql = $platform->modifyLimitQuery($sql, $limit, $offset);
		}
		$statement = $this->finishQuery($sql);

		return parent::prepare($statement);
	}

	/**
	 * Executes an, optionally parametrized, SQL query.
	 *
	 * If the query is parametrized, a prepared statement is used.
	 * If an SQLLogger is configured, the execution is logged.
	 *
	 * @param string                                      $sql  The SQL query to execute.
	 * @param array                                       $params The parameters to bind to the query, if any.
	 * @param array                                       $types  The types the previous parameters are in.
	 * @param \Doctrine\DBAL\Cache\QueryCacheProfile|null $qcp    The query cache profile, optional.
	 *
	 * @return Result The executed statement.
	 *
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function executeQuery(string $sql, array $params = [], $types = [], ?QueryCacheProfile $qcp = null): Result {
		$tables = $this->getQueriedTables($sql);
		$now = $this->clock->now()->getTimestamp();
		$dirtyTableWrites = [];
		foreach ($tables as $table) {
			$lastAccess = $this->tableDirtyWrites[$table] ?? 0;
			// Only very recent writes are considered dirty
			if ($lastAccess >= ($now - 3)) {
				$dirtyTableWrites[] = $table;
			}
		}
		if ($this->isTransactionActive()) {
			// Transacted queries go to the primary. The consistency of the primary guarantees that we can not run
			// into a dirty read.
		} elseif (count($dirtyTableWrites) === 0) {
			// No tables read that could have been written already in the same request and no transaction active
			// so we can switch back to the replica for reading as long as no writes happen that switch back to the primary
			// We cannot log here as this would log too early in the server boot process
			$this->ensureConnectedToReplica();
		} else {
			// Read to a table that has been written to previously
			// While this might not necessarily mean that we did a read after write it is an indication for a code path to check
			$this->logger->log(
				(int) ($this->systemConfig->getValue('loglevel_dirty_database_queries', null) ?? 0),
				'dirty table reads: ' . $sql,
				[
					'tables' => array_keys($this->tableDirtyWrites),
					'reads' => $tables,
					'exception' => new \Exception('dirty table reads: ' . $sql),
				],
			);
			// To prevent a dirty read on a replica that is slightly out of sync, we
			// switch back to the primary. This is detrimental for performance but
			// safer for consistency.
			$this->ensureConnectedToPrimary();
		}

		$sql = $this->finishQuery($sql);
		$this->queriesExecuted++;
		$this->logQueryToFile($sql);
		$types = array_map($this->convertParameterTypeToDoctrine(...), $types);
		return parent::executeQuery($sql, $params, $types, $qcp);
	}

	/**
	 * Helper function to get the list of tables affected by a given query
	 * used to track dirty tables that received a write with the current request
	 */
	private function getQueriedTables(string $sql): array {
		$re = '/(\*PREFIX\*\w+)/mi';
		preg_match_all($re, $sql, $matches);
		return array_map([$this, 'replaceTablePrefix'], $matches[0] ?? []);
	}

	/**
	 * @throws Exception
	 */
	public function executeUpdate(string $sql, array $params = [], array $types = []): int {
		return $this->executeStatement($sql, $params, $types);
	}

	/**
	 * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
	 * and returns the number of affected rows.
	 *
	 * This method supports PDO binding types as well as DBAL mapping types.
	 *
	 * @param string $sql  The SQL query.
	 * @param array  $params The query parameters.
	 * @param array  $types  The parameter types.
	 *
	 * @return int The number of affected rows, if the result is bigger than PHP_INT_MAX, PHP_INT_MAX is returned
	 *
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function executeStatement($sql, array $params = [], array $types = []): int {
		$tables = $this->getQueriedTables($sql);
		foreach ($tables as $table) {
			$this->tableDirtyWrites[$table] = $this->clock->now()->getTimestamp();
		}
		$sql = $this->finishQuery($sql);
		$this->queriesExecuted++;
		$this->logQueryToFile($sql);
		return (int)parent::executeStatement($sql, $params, $types);
	}

	protected function logQueryToFile(string $sql): void {
		$logFile = $this->systemConfig->getValue('query_log_file');
		if ($logFile !== '' && is_writable(dirname($logFile)) && (!file_exists($logFile) || is_writable($logFile))) {
			$prefix = '';
			if ($this->systemConfig->getValue('query_log_file_requestid') === 'yes') {
				$prefix .= Server::get(IRequestId::class)->getId() . "\t";
			}
			$postfix = '';
			if ($this->systemConfig->getValue('query_log_file_backtrace') === 'yes') {
				$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				array_pop($trace);
				$postfix .= '; ' . json_encode($trace);
			}

			// FIXME:  Improve to log the actual target db host
			$isPrimary = $this->connections['primary'] === $this->_conn;
			$prefix .= ' ' . ($isPrimary === true ? 'primary' : 'replica') . ' ';
			$prefix .= ' ' . $this->getTransactionNestingLevel() . ' ';

			file_put_contents(
				$this->systemConfig->getValue('query_log_file', ''),
				$prefix . $sql . $postfix . "\n",
				FILE_APPEND
			);
		}
	}

	/**
	 * Returns the ID of the last inserted row, or the last value from a sequence object,
	 * depending on the underlying driver.
	 *
	 * Note: This method may not return a meaningful or consistent result across different drivers,
	 * because the underlying database may not even support the notion of AUTO_INCREMENT/IDENTITY
	 * columns or sequences.
	 *
	 * @param string $seqName Name of the sequence object from which the ID should be returned.
	 *
	 * @return int the last inserted ID.
	 * @throws Exception
	 */
	public function lastInsertId($name = null): int {
		if ($name) {
			$name = $this->replaceTablePrefix($name);
		}
		return $this->adapter->lastInsertId($name);
	}

	/**
	 * @internal
	 * @throws Exception
	 */
	public function realLastInsertId($seqName = null) {
		return parent::lastInsertId($seqName);
	}

	/**
	 * Insert a row if the matching row does not exists. To accomplish proper race condition avoidance
	 * it is needed that there is also a unique constraint on the values. Then this method will
	 * catch the exception and return 0.
	 *
	 * @param string $table The table name (will replace *PREFIX* with the actual prefix)
	 * @param array $input data that should be inserted into the table  (column name => value)
	 * @param array|null $compare List of values that should be checked for "if not exists"
	 *				If this is null or an empty array, all keys of $input will be compared
	 *				Please note: text fields (clob) must not be used in the compare array
	 * @return int number of inserted rows
	 * @throws \Doctrine\DBAL\Exception
	 * @deprecated 15.0.0 - use unique index and "try { $db->insert() } catch (UniqueConstraintViolationException $e) {}" instead, because it is more reliable and does not have the risk for deadlocks - see https://github.com/nextcloud/server/pull/12371
	 */
	public function insertIfNotExist($table, $input, ?array $compare = null) {
		return $this->adapter->insertIfNotExist($table, $input, $compare);
	}

	public function insertIgnoreConflict(string $table, array $values) : int {
		return $this->adapter->insertIgnoreConflict($table, $values);
	}

	private function getType($value) {
		if (is_bool($value)) {
			return IQueryBuilder::PARAM_BOOL;
		} elseif (is_int($value)) {
			return IQueryBuilder::PARAM_INT;
		} else {
			return IQueryBuilder::PARAM_STR;
		}
	}

	/**
	 * Insert or update a row value
	 *
	 * @param string $table
	 * @param array $keys (column name => value)
	 * @param array $values (column name => value)
	 * @param array $updatePreconditionValues ensure values match preconditions (column name => value)
	 * @return int number of new rows
	 * @throws \OCP\DB\Exception
	 * @throws PreConditionNotMetException
	 */
	public function setValues(string $table, array $keys, array $values, array $updatePreconditionValues = []): int {
		try {
			$insertQb = $this->getQueryBuilder();
			$insertQb->insert($table)
				->values(
					array_map(function ($value) use ($insertQb) {
						return $insertQb->createNamedParameter($value, $this->getType($value));
					}, array_merge($keys, $values))
				);
			return $insertQb->executeStatement();
		} catch (\OCP\DB\Exception $e) {
			if (!in_array($e->getReason(), [
				\OCP\DB\Exception::REASON_CONSTRAINT_VIOLATION,
				\OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION,
			])
			) {
				throw $e;
			}

			// value already exists, try update
			$updateQb = $this->getQueryBuilder();
			$updateQb->update($table);
			foreach ($values as $name => $value) {
				$updateQb->set($name, $updateQb->createNamedParameter($value, $this->getType($value)));
			}
			$where = [];
			$whereValues = array_merge($keys, $updatePreconditionValues);
			foreach ($whereValues as $name => $value) {
				if ($value === '') {
					$where[] = $updateQb->expr()->emptyString(
						$name
					);
				} else {
					$where[] = $updateQb->expr()->eq(
						$name,
						$updateQb->createNamedParameter($value, $this->getType($value)),
						$this->getType($value)
					);
				}
			}
			$updateQb->where($updateQb->expr()->andX(...$where));
			$affected = $updateQb->executeStatement();

			if ($affected === 0 && !empty($updatePreconditionValues)) {
				throw new PreConditionNotMetException();
			}

			return 0;
		}
	}

	/**
	 * Create an exclusive read+write lock on a table
	 *
	 * @param string $tableName
	 *
	 * @throws \BadMethodCallException When trying to acquire a second lock
	 * @throws Exception
	 * @since 9.1.0
	 */
	public function lockTable($tableName) {
		if ($this->lockedTable !== null) {
			throw new \BadMethodCallException('Can not lock a new table until the previous lock is released.');
		}

		$tableName = $this->tablePrefix . $tableName;
		$this->lockedTable = $tableName;
		$this->adapter->lockTable($tableName);
	}

	/**
	 * Release a previous acquired lock again
	 *
	 * @throws Exception
	 * @since 9.1.0
	 */
	public function unlockTable() {
		$this->adapter->unlockTable();
		$this->lockedTable = null;
	}

	/**
	 * returns the error code and message as a string for logging
	 * works with DoctrineException
	 * @return string
	 */
	public function getError() {
		$msg = $this->errorCode() . ': ';
		$errorInfo = $this->errorInfo();
		if (!empty($errorInfo)) {
			$msg .= 'SQLSTATE = '.$errorInfo[0] . ', ';
			$msg .= 'Driver Code = '.$errorInfo[1] . ', ';
			$msg .= 'Driver Message = '.$errorInfo[2];
		}
		return $msg;
	}

	public function errorCode() {
		return -1;
	}

	public function errorInfo() {
		return [];
	}

	/**
	 * Drop a table from the database if it exists
	 *
	 * @param string $table table name without the prefix
	 *
	 * @throws Exception
	 */
	public function dropTable($table) {
		$table = $this->tablePrefix . trim($table);
		$schema = $this->createSchemaManager();
		if ($schema->tablesExist([$table])) {
			$schema->dropTable($table);
		}
	}

	/**
	 * Check if a table exists
	 *
	 * @param string $table table name without the prefix
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function tableExists($table) {
		$table = $this->tablePrefix . trim($table);
		$schema = $this->createSchemaManager();
		return $schema->tablesExist([$table]);
	}

	protected function finishQuery(string $statement): string {
		$statement = $this->replaceTablePrefix($statement);
		$statement = $this->adapter->fixupStatement($statement);
		if ($this->logRequestId) {
			return $statement . " /* reqid: " . $this->requestId . " */";
		} else {
			return $statement;
		}
	}

	// internal use
	/**
	 * @param string $statement
	 * @return string
	 */
	protected function replaceTablePrefix($statement) {
		return str_replace('*PREFIX*', $this->tablePrefix, $statement);
	}

	/**
	 * Check if a transaction is active
	 *
	 * @return bool
	 * @since 8.2.0
	 */
	public function inTransaction() {
		return $this->getTransactionNestingLevel() > 0;
	}

	/**
	 * Escape a parameter to be used in a LIKE query
	 *
	 * @param string $param
	 * @return string
	 */
	public function escapeLikeParameter($param) {
		return addcslashes($param, '\\_%');
	}

	/**
	 * Check whether or not the current database support 4byte wide unicode
	 *
	 * @return bool
	 * @since 11.0.0
	 */
	public function supports4ByteText() {
		if (!$this->getDatabasePlatform() instanceof MySQLPlatform) {
			return true;
		}
		return $this->getParams()['charset'] === 'utf8mb4';
	}


	/**
	 * Create the schema of the connected database
	 *
	 * @return Schema
	 * @throws Exception
	 */
	public function createSchema() {
		$migrator = $this->getMigrator();
		return $migrator->createSchema();
	}

	/**
	 * Migrate the database to the given schema
	 *
	 * @param Schema $toSchema
	 * @param bool $dryRun If true, will return the sql queries instead of running them.
	 *
	 * @throws Exception
	 *
	 * @return string|null Returns a string only if $dryRun is true.
	 */
	public function migrateToSchema(Schema $toSchema, bool $dryRun = false) {
		$migrator = $this->getMigrator();

		if ($dryRun) {
			return $migrator->generateChangeScript($toSchema);
		} else {
			$migrator->migrate($toSchema);
		}
	}

	private function getMigrator() {
		// TODO properly inject those dependencies
		$random = \OC::$server->get(ISecureRandom::class);
		$platform = $this->getDatabasePlatform();
		$config = \OC::$server->getConfig();
		$dispatcher = Server::get(\OCP\EventDispatcher\IEventDispatcher::class);
		if ($platform instanceof SQLitePlatform) {
			return new SQLiteMigrator($this, $config, $dispatcher);
		} elseif ($platform instanceof OraclePlatform) {
			return new OracleMigrator($this, $config, $dispatcher);
		} else {
			return new Migrator($this, $config, $dispatcher);
		}
	}

	public function beginTransaction(): void {
		if (!$this->inTransaction()) {
			$this->transactionActiveSince = microtime(true);
		}
		parent::beginTransaction();
	}

	public function commit(): void {
		parent::commit();
		if ($this->getTransactionNestingLevel() === 0) {
			$timeTook = microtime(true) - $this->transactionActiveSince;
			$this->transactionActiveSince = null;
			if ($timeTook > 1) {
				$this->logger->debug('Transaction took ' . $timeTook . 's', ['exception' => new \Exception('Transaction took ' . $timeTook . 's')]);
			}
		}
	}

	public function rollBack(): void {
		parent::rollBack();
		if ($this->getTransactionNestingLevel() === 0) {
			$timeTook = microtime(true) - $this->transactionActiveSince;
			$this->transactionActiveSince = null;
			if ($timeTook > 1) {
				$this->logger->debug('Transaction rollback took longer than 1s: ' . $timeTook, ['exception' => new \Exception('Long running transaction rollback')]);
			}
		}
	}

	private function reconnectIfNeeded(): void {
		if (
			!isset($this->lastConnectionCheck[$this->getConnectionName()]) ||
			time() <= $this->lastConnectionCheck[$this->getConnectionName()] + 30 ||
			$this->isTransactionActive()
		) {
			return;
		}

		try {
			$this->_conn->query($this->getDatabasePlatform()->getDummySelectSQL());
			$this->lastConnectionCheck[$this->getConnectionName()] = time();
		} catch (ConnectionLost|\Exception $e) {
			$this->logger->warning('Exception during connectivity check, closing and reconnecting', ['exception' => $e]);
			$this->close();
		}
	}

	private function getConnectionName(): string {
		return $this->isConnectedToPrimary() ? 'primary' : 'replica';
	}

	public function getDatabaseProvider(): string {
		$platform = $this->getDatabasePlatform();
		if ($platform instanceof MySQLPlatform) {
			return IDBConnection::PLATFORM_MYSQL;
		} elseif ($platform instanceof OraclePlatform) {
			return IDBConnection::PLATFORM_ORACLE;
		} elseif ($platform instanceof PostgreSQLPlatform) {
			return IDBConnection::PLATFORM_POSTGRES;
		} elseif ($platform instanceof SQLitePlatform) {
			return IDBConnection::PLATFORM_SQLITE;
		} else {
			throw new \Exception('Database ' . $platform::class . ' not supported');
		}
	}
}
