<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB\QueryBuilder;

use Doctrine\DBAL\Query\QueryException;
use OC\DB\ConnectionAdapter;
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
use OCP\DB\QueryBuilder\ConflictResolutionMode;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IFunctionBuilder;
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use OCP\IDBConnection;
use Override;
use Psr\Log\LoggerInterface;
use RuntimeException;

class QueryBuilder extends TypedQueryBuilder {
	private \Doctrine\DBAL\Query\QueryBuilder $queryBuilder;
	private QuoteHelper $helper;

	private bool $automaticTablePrefix = true;
	private bool $nonEmptyWhere = false;
	protected ?string $lastInsertedTable = null;
	private array $selectedColumns = [];

	/**
	 * Initializes a new QueryBuilder.
	 */
	public function __construct(
		private ConnectionAdapter $connection,
		private readonly SystemConfig $systemConfig,
		private readonly LoggerInterface $logger,
	) {
		$this->queryBuilder = new \Doctrine\DBAL\Query\QueryBuilder($this->connection->getInner());
		$this->helper = new QuoteHelper();
	}

	#[Override]
	public function automaticTablePrefix(bool $enabled): void {
		$this->automaticTablePrefix = $enabled;
	}

	#[Override]
	public function expr(): IExpressionBuilder {
		return match($this->connection->getDatabaseProvider()) {
			IDBConnection::PLATFORM_ORACLE => new OCIExpressionBuilder($this->connection, $this, $this->logger),
			IDBConnection::PLATFORM_POSTGRES => new PgSqlExpressionBuilder($this->connection, $this, $this->logger),
			IDBConnection::PLATFORM_MARIADB,
			IDBConnection::PLATFORM_MYSQL => new MySqlExpressionBuilder($this->connection, $this, $this->logger),
			IDBConnection::PLATFORM_SQLITE => new SqliteExpressionBuilder($this->connection, $this, $this->logger),
		};
	}

	#[Override]
	public function func(): IFunctionBuilder {
		return match($this->connection->getDatabaseProvider()) {
			IDBConnection::PLATFORM_ORACLE => new OCIFunctionBuilder($this->connection, $this, $this->helper),
			IDBConnection::PLATFORM_POSTGRES => new PgSqlFunctionBuilder($this->connection, $this, $this->helper),
			IDBConnection::PLATFORM_MARIADB,
			IDBConnection::PLATFORM_MYSQL => new FunctionBuilder($this->connection, $this, $this->helper),
			IDBConnection::PLATFORM_SQLITE => new SqliteFunctionBuilder($this->connection, $this, $this->helper),
		};
	}

	#[Override]
	public function getType(): int {
		return $this->queryBuilder->getType();
	}

	#[Override]
	public function getConnection(): IDBConnection {
		return $this->connection;
	}

	#[Override]
	public function getState(): int {
		$this->logger->debug(IQueryBuilder::class . '::' . __FUNCTION__ . ' is deprecated and will be removed soon.', ['exception' => new \Exception('Deprecated call to ' . __METHOD__)]);
		return $this->queryBuilder->getState();
	}

	private function prepareForExecute(): void {
		if ($this->systemConfig->getValue('log_query', false)) {
			try {
				$params = [];
				foreach ($this->getParameters() as $placeholder => $value) {
					if ($value instanceof \DateTimeInterface) {
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

		$tooLongOutputColumns = [];
		foreach ($this->getOutputColumns() as $column) {
			if (strlen($column) > 30) {
				$tooLongOutputColumns[] = $column;
			}
		}

		if (!empty($tooLongOutputColumns)) {
			$exception = new QueryException('More than 30 characters for an output column name are not allowed on Oracle.');
			$this->logger->error($exception->getMessage(), [
				'query' => $this->getSQL(),
				'columns' => $tooLongOutputColumns,
				'app' => 'core',
				'exception' => $exception,
			]);
		}

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

	#[Override]
	public function executeQuery(?IDBConnection $connection = null): IResult {
		if ($this->getType() !== \Doctrine\DBAL\Query\QueryBuilder::SELECT) {
			throw new RuntimeException('Invalid query type, expected SELECT query');
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

	#[Override]
	public function executeStatement(?IDBConnection $connection = null): int {
		if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::SELECT) {
			throw new RuntimeException('Invalid query type, expected INSERT, DELETE or UPDATE statement');
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

	#[Override]
	public function getSQL(): string {
		return $this->queryBuilder->getSQL();
	}

	#[Override]
	public function setParameter(string|int $key, mixed $value, string|null|int $type = null): self {
		$this->queryBuilder->setParameter($key, $value, $type);

		return $this;
	}

	#[Override]
	public function setParameters(array $params, array $types = []): self {
		$this->queryBuilder->setParameters($params, $types);

		return $this;
	}

	#[Override]
	public function getParameters(): array {
		return $this->queryBuilder->getParameters();
	}

	#[Override]
	public function getParameter(int|string $key): mixed {
		return $this->queryBuilder->getParameter($key);
	}

	#[Override]
	public function getParameterTypes(): array {
		/** @var list<self::PARAM_*|\Doctrine\DBAL\Types\Type> $types */
		$types = $this->queryBuilder->getParameterTypes();
		return array_map(function ($type): string|int {
			if ($type instanceof \Doctrine\DBAL\Types\Type) {
				/** @var self::PARAM_* $type */
				$type = \Doctrine\DBAL\Types\Type::lookupName($type);
			}
			return $type;
		}, $types);
	}

	#[Override]
	public function getParameterType(int|string $key): int|string {
		/** @var self::PARAM_*|\Doctrine\DBAL\Types\Type $type */
		$type = $this->queryBuilder->getParameterType($key);
		if ($type instanceof \Doctrine\DBAL\Types\Type) {
			/** @var self::PARAM_* $type */
			$type = \Doctrine\DBAL\Types\Type::lookupName($type);
		}
		return $type;
	}

	#[Override]
	public function setFirstResult(int $firstResult): self {
		$this->queryBuilder->setFirstResult($firstResult);

		return $this;
	}

	#[Override]
	public function getFirstResult(): int {
		return $this->queryBuilder->getFirstResult();
	}

	#[Override]
	public function setMaxResults(?int $maxResults): self {
		if ($maxResults === null) {
			$this->queryBuilder->setMaxResults($maxResults);
		} else {
			$this->queryBuilder->setMaxResults($maxResults);
		}

		return $this;
	}

	#[Override]
	public function getMaxResults(): ?int {
		return $this->queryBuilder->getMaxResults();
	}

	#[Override]
	public function select(...$selects): self {
		if (count($selects) === 1 && is_array($selects[0])) {
			$selects = $selects[0];
		}
		$this->addOutputColumns($selects);

		$this->queryBuilder->select(
			$this->helper->quoteColumnNames($selects)
		);

		return $this;
	}

	#[Override]
	public function selectAlias(string|IParameter|IQueryFunction|ILiteral $select, string $alias): self {
		$this->queryBuilder->addSelect(
			$this->helper->quoteColumnName($select) . ' AS ' . $this->helper->quoteColumnName($alias)
		);
		$this->addOutputColumns([$alias]);

		return $this;
	}

	#[Override]
	public function selectDistinct(string|array $select): self {
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

	#[Override]
	public function addSelect(...$select): self {
		if (count($select) === 1 && is_array($select[0])) {
			$select = $select[0];
		}
		$this->addOutputColumns($select);

		$this->queryBuilder->addSelect(
			$this->helper->quoteColumnNames($select)
		);

		return $this;
	}

	private function addOutputColumns(array $columns): void {
		foreach ($columns as $column) {
			if (is_array($column)) {
				$this->addOutputColumns($column);
			} elseif (is_string($column) && !str_contains($column, '*')) {
				if (str_contains(strtolower($column), ' as ')) {
					[, $column] = preg_split('/ as /i', $column);
				}
				if (str_contains($column, '.')) {
					[, $column] = explode('.', $column);
				}
				$this->selectedColumns[] = $column;
			}
		}
	}

	#[Override]
	public function getOutputColumns(): array {
		return array_unique($this->selectedColumns);
	}

	#[Override]
	public function delete(string $delete, ?string $alias = null): self {
		if ($alias !== null) {
			$this->logger->debug('DELETE queries with alias are no longer supported and the provided alias is ignored', ['exception' => new \InvalidArgumentException('Table alias provided for DELETE query')]);
		}

		$this->queryBuilder->delete(
			$this->getTableName($delete),
			$alias
		);

		return $this;
	}

	#[Override]
	public function update(string $update, ?string $alias = null): self {
		if ($alias !== null) {
			$this->logger->debug('UPDATE queries with alias are no longer supported and the provided alias is ignored', ['exception' => new \InvalidArgumentException('Table alias provided for UPDATE query')]);
		}

		$this->queryBuilder->update(
			$this->getTableName($update),
			$alias
		);

		return $this;
	}

	#[Override]
	public function insert(string $insert): self {
		$this->queryBuilder->insert(
			$this->getTableName($insert)
		);

		$this->lastInsertedTable = $insert;

		return $this;
	}

	#[Override]
	public function from(string|IQueryFunction $from, ?string $alias = null): self {
		$this->queryBuilder->from(
			$this->getTableName($from),
			$alias ? $this->quoteAlias($alias) : null,
		);

		return $this;
	}

	#[Override]
	public function join(
		string $fromAlias,
		string|IQueryFunction $join,
		?string $alias,
		string|ICompositeExpression|null $condition = null,
	): self {
		$this->queryBuilder->join(
			$this->quoteAlias($fromAlias),
			$this->getTableName($join),
			$alias ? $this->quoteAlias($alias) : null,
			$condition ? (string)$condition : null
		);

		return $this;
	}

	#[Override]
	public function innerJoin(
		string $fromAlias,
		string|IQueryFunction $join,
		?string $alias,
		string|ICompositeExpression|null $condition = null,
	): self {
		$this->queryBuilder->innerJoin(
			$this->quoteAlias($fromAlias),
			$this->getTableName($join),
			$alias ? $this->quoteAlias($alias) : null,
			$condition ? (string)$condition : null
		);

		return $this;
	}

	#[Override]
	public function leftJoin(
		string $fromAlias,
		string|IQueryFunction $join,
		?string $alias,
		string|ICompositeExpression|null $condition = null,
	): self {
		$this->queryBuilder->leftJoin(
			$this->quoteAlias($fromAlias),
			$this->getTableName($join),
			$alias ? $this->quoteAlias($alias) : null,
			$condition ? (string)$condition : null
		);

		return $this;
	}

	#[Override]
	public function rightJoin(string $fromAlias, string|IQueryFunction $join, ?string $alias, string|ICompositeExpression|null $condition = null): self {
		$this->queryBuilder->rightJoin(
			$this->quoteAlias($fromAlias),
			$this->getTableName($join),
			$alias ? $this->quoteAlias($alias) : null,
			$condition ? (string)$condition : null
		);

		return $this;
	}

	#[Override]
	public function set(string $key, ILiteral|IParameter|IQueryFunction|string $value): self {
		$this->queryBuilder->set(
			$this->helper->quoteColumnName($key),
			$this->helper->quoteColumnName($value)
		);

		return $this;
	}

	#[Override]
	public function where(...$predicates): self {
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

	#[Override]
	public function andWhere(...$where): self {
		$this->nonEmptyWhere = true;
		call_user_func_array(
			[$this->queryBuilder, 'andWhere'],
			$where
		);

		return $this;
	}

	#[Override]
	public function orWhere(...$where): self {
		$this->nonEmptyWhere = true;
		call_user_func_array(
			[$this->queryBuilder, 'orWhere'],
			$where
		);

		return $this;
	}

	#[Override]
	public function groupBy(...$groupBys): self {
		if (count($groupBys) === 1 && is_array($groupBys[0])) {
			$groupBys = $groupBys[0];
		}

		call_user_func_array(
			[$this->queryBuilder, 'groupBy'],
			$this->helper->quoteColumnNames($groupBys)
		);

		return $this;
	}

	#[Override]
	public function addGroupBy(...$groupBy): self {
		call_user_func_array(
			[$this->queryBuilder, 'addGroupBy'],
			$this->helper->quoteColumnNames($groupBy)
		);

		return $this;
	}

	#[Override]
	public function setValue(string $column, ILiteral|IParameter|IQueryFunction|string $value): self {
		$this->queryBuilder->setValue(
			$this->helper->quoteColumnName($column),
			(string)$value
		);

		return $this;
	}

	#[Override]
	public function values(array $values): self {
		$quotedValues = [];
		foreach ($values as $key => $value) {
			$quotedValues[$this->helper->quoteColumnName($key)] = $value;
		}

		$this->queryBuilder->values($quotedValues);

		return $this;
	}

	#[Override]
	public function having(...$having): self {
		call_user_func_array(
			[$this->queryBuilder, 'having'],
			$having
		);

		return $this;
	}

	#[Override]
	public function andHaving(...$having): self {
		call_user_func_array(
			[$this->queryBuilder, 'andHaving'],
			$having
		);

		return $this;
	}

	#[Override]
	public function orHaving(...$having): self {
		call_user_func_array(
			[$this->queryBuilder, 'orHaving'],
			$having
		);

		return $this;
	}

	#[Override]
	public function orderBy(string|ILiteral|IParameter|IQueryFunction $sort, ?string $order = null): self {
		if ($order !== null && !in_array(strtoupper($order), ['ASC', 'DESC'], true)) {
			$order = null;
		}

		$this->queryBuilder->orderBy(
			$this->helper->quoteColumnName($sort),
			$order
		);

		return $this;
	}

	#[Override]
	public function addOrderBy(string|ILiteral|IParameter|IQueryFunction $sort, ?string $order = null): self {
		if ($order !== null && !in_array(strtoupper($order), ['ASC', 'DESC'], true)) {
			$order = null;
		}

		$this->queryBuilder->addOrderBy(
			$this->helper->quoteColumnName($sort),
			$order
		);

		return $this;
	}

	#[Override]
	public function getQueryPart(string $queryPartName): mixed {
		$this->logger->debug(IQueryBuilder::class . '::' . __FUNCTION__ . ' is deprecated and will be removed soon.', ['exception' => new \Exception('Deprecated call to ' . __METHOD__)]);
		return $this->queryBuilder->getQueryPart($queryPartName);
	}

	#[Override]
	public function getQueryParts(): array {
		$this->logger->debug(IQueryBuilder::class . '::' . __FUNCTION__ . ' is deprecated and will be removed soon.', ['exception' => new \Exception('Deprecated call to ' . __METHOD__)]);
		return $this->queryBuilder->getQueryParts();
	}

	#[Override]
	public function resetQueryParts(?array $queryPartNames = null): self {
		$this->logger->debug(IQueryBuilder::class . '::' . __FUNCTION__ . ' is deprecated and will be removed soon.', ['exception' => new \Exception('Deprecated call to ' . __METHOD__)]);
		$this->queryBuilder->resetQueryParts($queryPartNames);

		return $this;
	}

	#[Override]
	public function resetQueryPart(string $queryPartName): self {
		$this->logger->debug(IQueryBuilder::class . '::' . __FUNCTION__ . ' is deprecated and will be removed soon.', ['exception' => new \Exception('Deprecated call to ' . __METHOD__)]);
		$this->queryBuilder->resetQueryPart($queryPartName);

		return $this;
	}

	#[Override]
	public function createNamedParameter(mixed $value, mixed $type = IQueryBuilder::PARAM_STR, ?string $placeHolder = null): IParameter {
		return new Parameter($this->queryBuilder->createNamedParameter($value, $type, $placeHolder));
	}

	#[Override]
	public function createPositionalParameter(mixed $value, mixed $type = IQueryBuilder::PARAM_STR): IParameter {
		return new Parameter($this->queryBuilder->createPositionalParameter($value, $type));
	}

	#[Override]
	public function createParameter(string $name): IParameter {
		return new Parameter(':' . $name);
	}

	#[Override]
	public function createFunction(string $call): IQueryFunction {
		return new QueryFunction($call);
	}

	#[Override]
	public function getLastInsertId(): int {
		if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::INSERT && $this->lastInsertedTable !== null) {
			// lastInsertId() needs the prefix but no quotes
			$table = $this->prefixTableName($this->lastInsertedTable);
			return $this->connection->lastInsertId($table);
		}

		throw new \BadMethodCallException('Invalid call to getLastInsertId without using insert() before.');
	}

	#[Override]
	public function getTableName(string|IQueryFunction $table): string {
		if ($table instanceof IQueryFunction) {
			return (string)$table;
		}

		$table = $this->prefixTableName($table);
		return $this->helper->quoteColumnName($table);
	}

	#[Override]
	public function prefixTableName(string $table): string {
		if ($this->automaticTablePrefix === false || str_starts_with($table, '*PREFIX*')) {
			return $table;
		}

		return '*PREFIX*' . $table;
	}

	#[Override]
	public function getColumnName(string $column, string $tableAlias = ''): string {
		if ($tableAlias !== '') {
			$tableAlias .= '.';
		}

		return $this->helper->quoteColumnName($tableAlias . $column);
	}

	public function quoteAlias(string $alias): string {
		if ($alias === '') {
			return $alias;
		}

		return $this->helper->quoteColumnName($alias);
	}

	public function escapeLikeParameter(string $parameter): string {
		return $this->connection->escapeLikeParameter($parameter);
	}

	#[Override]
	public function hintShardKey(string $column, mixed $value, bool $overwrite = false): self {
		return $this;
	}

	#[Override]
	public function runAcrossAllShards(): self {
		// noop
		return $this;
	}

	#[Override]
	public function forUpdate(ConflictResolutionMode $conflictResolutionMode = ConflictResolutionMode::Ordinary): self {
		match ($conflictResolutionMode) {
			ConflictResolutionMode::Ordinary => $this->queryBuilder->forUpdate(),
			ConflictResolutionMode::SkipLocked => $this->queryBuilder->forUpdate(\Doctrine\DBAL\Query\ForUpdate\ConflictResolutionMode::SKIP_LOCKED),
		};
		return $this;
	}
}
