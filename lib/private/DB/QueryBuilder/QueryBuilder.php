<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB\QueryBuilder;

use Doctrine\DBAL\Query\QueryException;
use OC\DB\ConnectionAdapter;
use OC\DB\Exceptions\DbalException;
use OC\DB\QueryBuilder\ExpressionBuilder\MySqlExpressionBuilder;
use OC\DB\QueryBuilder\ExpressionBuilder\OCIExpressionBuilder;
use OC\DB\QueryBuilder\ExpressionBuilder\PgSqlExpressionBuilder;
use OC\DB\QueryBuilder\ExpressionBuilder\SqliteExpressionBuilder;
use OC\DB\QueryBuilder\FunctionBuilder\FunctionBuilder;
use OC\DB\QueryBuilder\FunctionBuilder\OCIFunctionBuilder;
use OC\DB\QueryBuilder\FunctionBuilder\PgSqlFunctionBuilder;
use OC\DB\QueryBuilder\FunctionBuilder\SqliteFunctionBuilder;
use OC\SystemConfig;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class QueryBuilder implements IQueryBuilder {
	/** @var ConnectionAdapter */
	private $connection;

	/** @var SystemConfig */
	private $systemConfig;

	private LoggerInterface $logger;

	/** @var \Doctrine\DBAL\Query\QueryBuilder */
	private $queryBuilder;

	/** @var QuoteHelper */
	private $helper;

	/** @var bool */
	private $automaticTablePrefix = true;
	private bool $nonEmptyWhere = false;

	/** @var string */
	protected $lastInsertedTable;
	private array $selectedColumns = [];

	/**
	 * Initializes a new QueryBuilder.
	 *
	 * @param ConnectionAdapter $connection
	 * @param SystemConfig $systemConfig
	 */
	public function __construct(ConnectionAdapter $connection, SystemConfig $systemConfig, LoggerInterface $logger) {
		$this->connection = $connection;
		$this->systemConfig = $systemConfig;
		$this->logger = $logger;
		$this->queryBuilder = new \Doctrine\DBAL\Query\QueryBuilder($this->connection->getInner());
		$this->helper = new QuoteHelper();
	}

	public function automaticTablePrefix($enabled) {
		$this->automaticTablePrefix = (bool)$enabled;
	}

	public function expr() {
		return match($this->connection->getDatabaseProvider()) {
			IDBConnection::PLATFORM_ORACLE => new OCIExpressionBuilder($this->connection, $this, $this->logger),
			IDBConnection::PLATFORM_POSTGRES => new PgSqlExpressionBuilder($this->connection, $this, $this->logger),
			IDBConnection::PLATFORM_MYSQL => new MySqlExpressionBuilder($this->connection, $this, $this->logger),
			IDBConnection::PLATFORM_SQLITE => new SqliteExpressionBuilder($this->connection, $this, $this->logger),
		};
	}

	public function func() {
		return match($this->connection->getDatabaseProvider()) {
			IDBConnection::PLATFORM_ORACLE => new OCIFunctionBuilder($this->connection, $this, $this->helper),
			IDBConnection::PLATFORM_POSTGRES => new PgSqlFunctionBuilder($this->connection, $this, $this->helper),
			IDBConnection::PLATFORM_MYSQL => new FunctionBuilder($this->connection, $this, $this->helper),
			IDBConnection::PLATFORM_SQLITE => new SqliteFunctionBuilder($this->connection, $this, $this->helper),
		};
	}

	public function getType() {
		return $this->queryBuilder->getType();
	}

	public function getConnection() {
		return $this->connection;
	}

	public function getState() {
		$this->logger->debug(IQueryBuilder::class . '::' . __FUNCTION__ . ' is deprecated and will be removed soon.', ['exception' => new \Exception('Deprecated call to ' . __METHOD__)]);
		return $this->queryBuilder->getState();
	}

	private function prepareForExecute() {
		if ($this->systemConfig->getValue('log_query', false)) {
			try {
				$params = [];
				foreach ($this->getParameters() as $placeholder => $value) {
					if ($value instanceof \DateTime) {
						$params[] = $placeholder . ' => DateTime:\'' . $value->format('c') . '\'';
					} elseif (is_array($value)) {
						$params[] = $placeholder . ' => (\'' . implode('\', \'', $value) . '\')';
					} else {
						$params[] = $placeholder . ' => \'' . $value . '\'';
					}
				}
				if (empty($params)) {
					$this->logger->debug('DB QueryBuilder: \'{query}\'', [
						'query' => $this->getSQL(),
						'app' => 'core',
					]);
				} else {
					$this->logger->debug('DB QueryBuilder: \'{query}\' with parameters: {params}', [
						'query' => $this->getSQL(),
						'params' => implode(', ', $params),
						'app' => 'core',
					]);
				}
			} catch (\Error $e) {
				// likely an error during conversion of $value to string
				$this->logger->error('DB QueryBuilder: error trying to log SQL query', ['exception' => $e]);
			}
		}

		// if (!empty($this->getQueryPart('select'))) {
		// $select = $this->getQueryPart('select');
		// $hasSelectAll = array_filter($select, static function ($s) {
		// return $s === '*';
		// });
		// $hasSelectSpecific = array_filter($select, static function ($s) {
		// return $s !== '*';
		// });

		// if (empty($hasSelectAll) === empty($hasSelectSpecific)) {
		// $exception = new QueryException('Query is selecting * and specific values in the same query. This is not supported in Oracle.');
		// $this->logger->error($exception->getMessage(), [
		// 'query' => $this->getSQL(),
		// 'app' => 'core',
		// 'exception' => $exception,
		// ]);
		// }
		// }

		$numberOfParameters = 0;
		$hasTooLargeArrayParameter = false;
		foreach ($this->getParameters() as $parameter) {
			if (is_array($parameter)) {
				$count = count($parameter);
				$numberOfParameters += $count;
				$hasTooLargeArrayParameter = $hasTooLargeArrayParameter || ($count > 1000);
			}
		}

		if ($hasTooLargeArrayParameter) {
			$exception = new QueryException('More than 1000 expressions in a list are not allowed on Oracle.');
			$this->logger->error($exception->getMessage(), [
				'query' => $this->getSQL(),
				'app' => 'core',
				'exception' => $exception,
			]);
		}

		if ($numberOfParameters > 65535) {
			$exception = new QueryException('The number of parameters must not exceed 65535. Restriction by PostgreSQL.');
			$this->logger->error($exception->getMessage(), [
				'query' => $this->getSQL(),
				'app' => 'core',
				'exception' => $exception,
			]);
		}
	}

	public function execute(?IDBConnection $connection = null) {
		try {
			if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::SELECT) {
				return $this->executeQuery($connection);
			} else {
				return $this->executeStatement($connection);
			}
		} catch (DBALException $e) {
			// `IQueryBuilder->execute` never wrapped the exception, but `executeQuery` and `executeStatement` do
			/** @var \Doctrine\DBAL\Exception $previous */
			$previous = $e->getPrevious();

			throw $previous;
		}
	}

	public function executeQuery(?IDBConnection $connection = null): IResult {
		if ($this->getType() !== \Doctrine\DBAL\Query\QueryBuilder::SELECT) {
			throw new \RuntimeException('Invalid query type, expected SELECT query');
		}

		$this->prepareForExecute();
		if (!$connection) {
			$connection = $this->connection;
		}

		return $connection->executeQuery(
			$this->getSQL(),
			$this->getParameters(),
			$this->getParameterTypes(),
		);
	}

	public function executeStatement(?IDBConnection $connection = null): int {
		if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::SELECT) {
			throw new \RuntimeException('Invalid query type, expected INSERT, DELETE or UPDATE statement');
		}

		$this->prepareForExecute();
		if (!$connection) {
			$connection = $this->connection;
		}

		return $connection->executeStatement(
			$this->getSQL(),
			$this->getParameters(),
			$this->getParameterTypes(),
		);
	}


	public function getSQL() {
		return $this->queryBuilder->getSQL();
	}

	public function setParameter($key, $value, $type = null) {
		$this->queryBuilder->setParameter($key, $value, $type);

		return $this;
	}

	public function setParameters(array $params, array $types = []) {
		$this->queryBuilder->setParameters($params, $types);

		return $this;
	}

	public function getParameters() {
		return $this->queryBuilder->getParameters();
	}

	public function getParameter($key) {
		return $this->queryBuilder->getParameter($key);
	}

	public function getParameterTypes() {
		return $this->queryBuilder->getParameterTypes();
	}

	public function getParameterType($key) {
		return $this->queryBuilder->getParameterType($key);
	}

	public function setFirstResult($firstResult) {
		$this->queryBuilder->setFirstResult((int)$firstResult);

		return $this;
	}

	public function getFirstResult() {
		return $this->queryBuilder->getFirstResult();
	}

	public function setMaxResults($maxResults) {
		if ($maxResults === null) {
			$this->queryBuilder->setMaxResults($maxResults);
		} else {
			$this->queryBuilder->setMaxResults((int)$maxResults);
		}

		return $this;
	}

	public function getMaxResults() {
		return $this->queryBuilder->getMaxResults();
	}

	public function select(...$selects) {
		if (count($selects) === 1 && is_array($selects[0])) {
			$selects = $selects[0];
		}
		$this->addOutputColumns($selects);

		$this->queryBuilder->select(
			$this->helper->quoteColumnNames($selects)
		);

		return $this;
	}

	public function selectAlias($select, $alias) {
		$this->queryBuilder->addSelect(
			$this->helper->quoteColumnName($select) . ' AS ' . $this->helper->quoteColumnName($alias)
		);
		$this->addOutputColumns([$alias]);

		return $this;
	}

	public function selectDistinct($select) {
		if (!is_array($select)) {
			$select = [$select];
		}
		$this->addOutputColumns($select);

		$quotedSelect = $this->helper->quoteColumnNames($select);

		$this->queryBuilder->addSelect(
			'DISTINCT ' . implode(', ', $quotedSelect)
		);

		return $this;
	}

	public function addSelect(...$selects) {
		if (count($selects) === 1 && is_array($selects[0])) {
			$selects = $selects[0];
		}
		$this->addOutputColumns($selects);

		$this->queryBuilder->addSelect(
			$this->helper->quoteColumnNames($selects)
		);

		return $this;
	}

	private function addOutputColumns(array $columns) {
		foreach ($columns as $column) {
			if (is_array($column)) {
				$this->addOutputColumns($column);
			} elseif (is_string($column) && !str_contains($column, '*')) {
				if (str_contains($column, '.')) {
					[, $column] = explode('.', $column);
				}
				$this->selectedColumns[] = $column;
			}
		}
	}

	public function getOutputColumns(): array {
		return array_unique(array_map(function (string $column) {
			if (str_contains($column, '.')) {
				[, $column] = explode('.', $column);
				return $column;
			} else {
				return $column;
			}
		}, $this->selectedColumns));
	}

	public function delete($delete = null, $alias = null) {
		if ($alias !== null) {
			$this->logger->debug('DELETE queries with alias are no longer supported and the provided alias is ignored', ['exception' => new \InvalidArgumentException('Table alias provided for DELETE query')]);
		}

		$this->queryBuilder->delete(
			$this->getTableName($delete),
			$alias
		);

		return $this;
	}

	public function update($update = null, $alias = null) {
		if ($alias !== null) {
			$this->logger->debug('UPDATE queries with alias are no longer supported and the provided alias is ignored', ['exception' => new \InvalidArgumentException('Table alias provided for UPDATE query')]);
		}

		$this->queryBuilder->update(
			$this->getTableName($update),
			$alias
		);

		return $this;
	}

	public function insert($insert = null) {
		$this->queryBuilder->insert(
			$this->getTableName($insert)
		);

		$this->lastInsertedTable = $insert;

		return $this;
	}

	public function from($from, $alias = null) {
		$this->queryBuilder->from(
			$this->getTableName($from),
			$this->quoteAlias($alias)
		);

		return $this;
	}

	public function join($fromAlias, $join, $alias, $condition = null) {
		$this->queryBuilder->join(
			$this->quoteAlias($fromAlias),
			$this->getTableName($join),
			$this->quoteAlias($alias),
			$condition
		);

		return $this;
	}

	public function innerJoin($fromAlias, $join, $alias, $condition = null) {
		$this->queryBuilder->innerJoin(
			$this->quoteAlias($fromAlias),
			$this->getTableName($join),
			$this->quoteAlias($alias),
			$condition
		);

		return $this;
	}

	public function leftJoin($fromAlias, $join, $alias, $condition = null) {
		$this->queryBuilder->leftJoin(
			$this->quoteAlias($fromAlias),
			$this->getTableName($join),
			$this->quoteAlias($alias),
			$condition
		);

		return $this;
	}

	public function rightJoin($fromAlias, $join, $alias, $condition = null) {
		$this->queryBuilder->rightJoin(
			$this->quoteAlias($fromAlias),
			$this->getTableName($join),
			$this->quoteAlias($alias),
			$condition
		);

		return $this;
	}

	public function set($key, $value) {
		$this->queryBuilder->set(
			$this->helper->quoteColumnName($key),
			$this->helper->quoteColumnName($value)
		);

		return $this;
	}

	public function where(...$predicates) {
		if ($this->nonEmptyWhere && $this->systemConfig->getValue('debug', false)) {
			// Only logging a warning, not throwing for now.
			$e = new QueryException('Using where() on non-empty WHERE part, please verify it is intentional to not call andWhere() or orWhere() instead. Otherwise consider creating a new query builder object or call resetQueryPart(\'where\') first.');
			$this->logger->warning($e->getMessage(), ['exception' => $e]);
		}

		$this->nonEmptyWhere = true;

		call_user_func_array(
			[$this->queryBuilder, 'where'],
			$predicates
		);

		return $this;
	}

	public function andWhere(...$where) {
		$this->nonEmptyWhere = true;
		call_user_func_array(
			[$this->queryBuilder, 'andWhere'],
			$where
		);

		return $this;
	}

	public function orWhere(...$where) {
		$this->nonEmptyWhere = true;
		call_user_func_array(
			[$this->queryBuilder, 'orWhere'],
			$where
		);

		return $this;
	}

	public function groupBy(...$groupBys) {
		if (count($groupBys) === 1 && is_array($groupBys[0])) {
			$groupBys = $groupBys[0];
		}

		call_user_func_array(
			[$this->queryBuilder, 'groupBy'],
			$this->helper->quoteColumnNames($groupBys)
		);

		return $this;
	}

	public function addGroupBy(...$groupBy) {
		call_user_func_array(
			[$this->queryBuilder, 'addGroupBy'],
			$this->helper->quoteColumnNames($groupBy)
		);

		return $this;
	}

	public function setValue($column, $value) {
		$this->queryBuilder->setValue(
			$this->helper->quoteColumnName($column),
			(string)$value
		);

		return $this;
	}

	public function values(array $values) {
		$quotedValues = [];
		foreach ($values as $key => $value) {
			$quotedValues[$this->helper->quoteColumnName($key)] = $value;
		}

		$this->queryBuilder->values($quotedValues);

		return $this;
	}

	public function having(...$having) {
		call_user_func_array(
			[$this->queryBuilder, 'having'],
			$having
		);

		return $this;
	}

	public function andHaving(...$having) {
		call_user_func_array(
			[$this->queryBuilder, 'andHaving'],
			$having
		);

		return $this;
	}

	public function orHaving(...$having) {
		call_user_func_array(
			[$this->queryBuilder, 'orHaving'],
			$having
		);

		return $this;
	}

	public function orderBy($sort, $order = null) {
		$this->queryBuilder->orderBy(
			$this->helper->quoteColumnName($sort),
			$order
		);

		return $this;
	}

	public function addOrderBy($sort, $order = null) {
		$this->queryBuilder->addOrderBy(
			$this->helper->quoteColumnName($sort),
			$order
		);

		return $this;
	}

	public function getQueryPart($queryPartName) {
		$this->logger->debug(IQueryBuilder::class . '::' . __FUNCTION__ . ' is deprecated and will be removed soon.', ['exception' => new \Exception('Deprecated call to ' . __METHOD__)]);
		return $this->queryBuilder->getQueryPart($queryPartName);
	}

	public function getQueryParts() {
		$this->logger->debug(IQueryBuilder::class . '::' . __FUNCTION__ . ' is deprecated and will be removed soon.', ['exception' => new \Exception('Deprecated call to ' . __METHOD__)]);
		return $this->queryBuilder->getQueryParts();
	}

	public function resetQueryParts($queryPartNames = null) {
		$this->logger->debug(IQueryBuilder::class . '::' . __FUNCTION__ . ' is deprecated and will be removed soon.', ['exception' => new \Exception('Deprecated call to ' . __METHOD__)]);
		$this->queryBuilder->resetQueryParts($queryPartNames);

		return $this;
	}

	public function resetQueryPart($queryPartName) {
		$this->logger->debug(IQueryBuilder::class . '::' . __FUNCTION__ . ' is deprecated and will be removed soon.', ['exception' => new \Exception('Deprecated call to ' . __METHOD__)]);
		$this->queryBuilder->resetQueryPart($queryPartName);

		return $this;
	}

	public function createNamedParameter($value, $type = IQueryBuilder::PARAM_STR, $placeHolder = null) {
		return new Parameter($this->queryBuilder->createNamedParameter($value, $type, $placeHolder));
	}

	public function createPositionalParameter($value, $type = IQueryBuilder::PARAM_STR) {
		return new Parameter($this->queryBuilder->createPositionalParameter($value, $type));
	}

	public function createParameter($name) {
		return new Parameter(':' . $name);
	}

	public function createFunction($call) {
		return new QueryFunction($call);
	}

	public function getLastInsertId(): int {
		if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::INSERT && $this->lastInsertedTable) {
			// lastInsertId() needs the prefix but no quotes
			$table = $this->prefixTableName($this->lastInsertedTable);
			return $this->connection->lastInsertId($table);
		}

		throw new \BadMethodCallException('Invalid call to getLastInsertId without using insert() before.');
	}

	public function getTableName($table) {
		if ($table instanceof IQueryFunction) {
			return (string)$table;
		}

		$table = $this->prefixTableName($table);
		return $this->helper->quoteColumnName($table);
	}

	public function prefixTableName(string $table): string {
		if ($this->automaticTablePrefix === false || str_starts_with($table, '*PREFIX*')) {
			return $table;
		}

		return '*PREFIX*' . $table;
	}

	public function getColumnName($column, $tableAlias = '') {
		if ($tableAlias !== '') {
			$tableAlias .= '.';
		}

		return $this->helper->quoteColumnName($tableAlias . $column);
	}

	/**
	 * Returns the column name quoted and with table alias prefix as needed by the implementation
	 *
	 * @param string $alias
	 * @return string
	 */
	public function quoteAlias($alias) {
		if ($alias === '' || $alias === null) {
			return $alias;
		}

		return $this->helper->quoteColumnName($alias);
	}

	public function escapeLikeParameter(string $parameter): string {
		return $this->connection->escapeLikeParameter($parameter);
	}

	public function hintShardKey(string $column, mixed $value, bool $overwrite = false) {
		return $this;
	}

	public function runAcrossAllShards() {
		// noop
		return $this;
	}

}
