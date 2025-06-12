<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\DB\QueryBuilder\Partitioned;

use OC\DB\QueryBuilder\CompositeExpression;
use OC\DB\QueryBuilder\QuoteHelper;
use OC\DB\QueryBuilder\Sharded\AutoIncrementHandler;
use OC\DB\QueryBuilder\Sharded\ShardConnectionManager;
use OC\DB\QueryBuilder\Sharded\ShardedQueryBuilder;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use OCP\IDBConnection;

/**
 * A special query builder that automatically splits queries that span across multiple database partitions[1].
 *
 * This is done by inspecting the query as it's being built, and when a cross-partition join is detected,
 * the part of the query that touches the partition is split of into a different sub-query.
 * Then, when the query is executed, the results from the sub-queries are automatically merged.
 *
 * This whole process is intended to be transparent to any code using the query builder, however it does impose some extra
 * limitation for queries that work cross-partition. See the documentation from `InvalidPartitionedQueryException` for more details.
 *
 * When a join is created in the query, this builder checks if it belongs to the same partition as the table from the
 * original FROM/UPDATE/DELETE/INSERT and if not, creates a new "sub query" for the partition.
 * Then for every part that is added the query, the part is analyzed to determine which partition the query part is referencing
 * and the query part is added to the sub query for that partition.
 *
 * [1]: A set of tables which can't be queried together with the rest of the tables, such as when sharding is used.
 */
class PartitionedQueryBuilder extends ShardedQueryBuilder {
	/** @var array<string, PartitionQuery> $splitQueries */
	private array $splitQueries = [];
	/** @var list<PartitionSplit> */
	private array $partitions = [];

	/** @var array{'select': string|array, 'alias': ?string}[] */
	private array $selects = [];
	private ?PartitionSplit $mainPartition = null;
	private bool $hasPositionalParameter = false;
	private QuoteHelper $quoteHelper;
	private ?int $limit = null;
	private ?int $offset = null;

	public function __construct(
		IQueryBuilder $builder,
		array $shardDefinitions,
		ShardConnectionManager $shardConnectionManager,
		AutoIncrementHandler $autoIncrementHandler,
	) {
		parent::__construct($builder, $shardDefinitions, $shardConnectionManager, $autoIncrementHandler);
		$this->quoteHelper = new QuoteHelper();
	}

	private function newQuery(): IQueryBuilder {
		// get a fresh, non-partitioning query builder
		$builder = $this->builder->getConnection()->getQueryBuilder();
		if ($builder instanceof PartitionedQueryBuilder) {
			$builder = $builder->builder;
		}

		return new ShardedQueryBuilder(
			$builder,
			$this->shardDefinitions,
			$this->shardConnectionManager,
			$this->autoIncrementHandler,
		);
	}

	// we need to save selects until we know all the table aliases
	public function select(...$selects) {
		if (count($selects) === 1 && is_array($selects[0])) {
			$selects = $selects[0];
		}
		$this->selects = [];
		$this->addSelect(...$selects);
		return $this;
	}

	public function addSelect(...$select) {
		$select = array_map(function ($select) {
			return ['select' => $select, 'alias' => null];
		}, $select);
		$this->selects = array_merge($this->selects, $select);
		return $this;
	}

	public function selectAlias($select, $alias) {
		$this->selects[] = ['select' => $select, 'alias' => $alias];
		return $this;
	}

	/**
	 * Ensure that a column is being selected by the query
	 *
	 * This is mainly used to ensure that the returned rows from both sides of a partition contains the columns of the join predicate
	 *
	 * @param string|IQueryFunction $column
	 * @return void
	 */
	private function ensureSelect(string|IQueryFunction $column, ?string $alias = null): void {
		$checkColumn = $alias ?: $column;
		if (str_contains($checkColumn, '.')) {
			[$table, $checkColumn] = explode('.', $checkColumn);
			$partition = $this->getPartition($table);
		} else {
			$partition = null;
		}
		foreach ($this->selects as $select) {
			$select = $select['select'];
			if (!is_string($select)) {
				continue;
			}

			if (str_contains($select, '.')) {
				[$table, $select] = explode('.', $select);
				$selectPartition = $this->getPartition($table);
			} else {
				$selectPartition = null;
			}
			if (
				($select === $checkColumn || $select === '*') &&
				$selectPartition === $partition
			) {
				return;
			}
		}
		if ($alias) {
			$this->selectAlias($column, $alias);
		} else {
			$this->addSelect($column);
		}
	}

	/**
	 * Distribute the select statements to the correct partition
	 *
	 * This is done at the end instead of when the `select` call is made, because the `select` calls are generally done
	 * before we know what tables are involved in the query
	 *
	 * @return void
	 */
	private function applySelects(): void {
		foreach ($this->selects as $select) {
			foreach ($this->partitions as $partition) {
				if (is_string($select['select']) && (
					$select['select'] === '*' ||
					$partition->isColumnInPartition($select['select']))
				) {
					if (isset($this->splitQueries[$partition->name])) {
						if ($select['alias']) {
							$this->splitQueries[$partition->name]->query->selectAlias($select['select'], $select['alias']);
						} else {
							$this->splitQueries[$partition->name]->query->addSelect($select['select']);
						}
						if ($select['select'] !== '*') {
							continue 2;
						}
					}
				}
			}

			if ($select['alias']) {
				parent::selectAlias($select['select'], $select['alias']);
			} else {
				parent::addSelect($select['select']);
			}
		}
		$this->selects = [];
	}


	public function addPartition(PartitionSplit $partition): void {
		$this->partitions[] = $partition;
	}

	private function getPartition(string $table): ?PartitionSplit {
		foreach ($this->partitions as $partition) {
			if ($partition->containsTable($table) || $partition->containsAlias($table)) {
				return $partition;
			}
		}
		return null;
	}

	public function from($from, $alias = null) {
		if (is_string($from) && $partition = $this->getPartition($from)) {
			$this->mainPartition = $partition;
			if ($alias) {
				$this->mainPartition->addAlias($from, $alias);
			}
		}
		return parent::from($from, $alias);
	}

	public function innerJoin($fromAlias, $join, $alias, $condition = null): self {
		return $this->join($fromAlias, $join, $alias, $condition);
	}

	public function leftJoin($fromAlias, $join, $alias, $condition = null): self {
		return $this->join($fromAlias, (string)$join, $alias, $condition, PartitionQuery::JOIN_MODE_LEFT);
	}

	public function join($fromAlias, $join, $alias, $condition = null, $joinMode = PartitionQuery::JOIN_MODE_INNER): self {
		$partition = $this->getPartition($join);
		$fromPartition = $this->getPartition($fromAlias);
		if ($partition && $partition !== $this->mainPartition) {
			// join from the main db to a partition

			$joinCondition = JoinCondition::parse($condition, $join, $alias, $fromAlias);
			$partition->addAlias($join, $alias);

			if (!isset($this->splitQueries[$partition->name])) {
				$this->splitQueries[$partition->name] = new PartitionQuery(
					$this->newQuery(),
					$joinCondition->fromAlias ?? $joinCondition->fromColumn, $joinCondition->toAlias ?? $joinCondition->toColumn,
					$joinMode
				);
				$this->splitQueries[$partition->name]->query->from($join, $alias);
				$this->ensureSelect($joinCondition->fromColumn, $joinCondition->fromAlias);
				$this->ensureSelect($joinCondition->toColumn, $joinCondition->toAlias);
			} else {
				$query = $this->splitQueries[$partition->name]->query;
				if ($partition->containsAlias($fromAlias)) {
					$query->innerJoin($fromAlias, $join, $alias, $condition);
				} else {
					throw new InvalidPartitionedQueryException("Can't join across partition boundaries more than once");
				}
			}
			$this->splitQueries[$partition->name]->query->andWhere(...$joinCondition->toConditions);
			parent::andWhere(...$joinCondition->fromConditions);
			return $this;
		} elseif ($fromPartition && $fromPartition !== $partition) {
			// join from partition, to the main db

			$joinCondition = JoinCondition::parse($condition, $join, $alias, $fromAlias);
			if (str_starts_with($fromPartition->name, 'from_')) {
				$partitionName = $fromPartition->name;
			} else {
				$partitionName = 'from_' . $fromPartition->name;
			}

			if (!isset($this->splitQueries[$partitionName])) {
				$newPartition = new PartitionSplit($partitionName, [$join]);
				$newPartition->addAlias($join, $alias);
				$this->partitions[] = $newPartition;

				$this->splitQueries[$partitionName] = new PartitionQuery(
					$this->newQuery(),
					$joinCondition->fromAlias ?? $joinCondition->fromColumn, $joinCondition->toAlias ?? $joinCondition->toColumn,
					$joinMode
				);
				$this->ensureSelect($joinCondition->fromColumn, $joinCondition->fromAlias);
				$this->ensureSelect($joinCondition->toColumn, $joinCondition->toAlias);
				$this->splitQueries[$partitionName]->query->from($join, $alias);
				$this->splitQueries[$partitionName]->query->andWhere(...$joinCondition->toConditions);
				parent::andWhere(...$joinCondition->fromConditions);
			} else {
				$fromPartition->addTable($join);
				$fromPartition->addAlias($join, $alias);

				$query = $this->splitQueries[$partitionName]->query;
				$query->innerJoin($fromAlias, $join, $alias, $condition);
			}
			return $this;
		} else {
			// join within the main db or a partition
			if ($joinMode === PartitionQuery::JOIN_MODE_INNER) {
				return parent::innerJoin($fromAlias, $join, $alias, $condition);
			} elseif ($joinMode === PartitionQuery::JOIN_MODE_LEFT) {
				return parent::leftJoin($fromAlias, $join, $alias, $condition);
			} elseif ($joinMode === PartitionQuery::JOIN_MODE_RIGHT) {
				return parent::rightJoin($fromAlias, $join, $alias, $condition);
			} else {
				throw new \InvalidArgumentException("Invalid join mode: $joinMode");
			}
		}
	}

	/**
	 * Flatten a list of predicates by merging the parts of any "AND" expression into the list of predicates
	 *
	 * @param array $predicates
	 * @return array
	 */
	private function flattenPredicates(array $predicates): array {
		$result = [];
		foreach ($predicates as $predicate) {
			if ($predicate instanceof CompositeExpression && $predicate->getType() === CompositeExpression::TYPE_AND) {
				$result = array_merge($result, $this->flattenPredicates($predicate->getParts()));
			} else {
				$result[] = $predicate;
			}
		}
		return $result;
	}

	/**
	 * Split an array of predicates (WHERE query parts) by the partition they reference
	 *
	 * @param array $predicates
	 * @return array<string, array>
	 */
	private function splitPredicatesByParts(array $predicates): array {
		$predicates = $this->flattenPredicates($predicates);

		$partitionPredicates = [];
		foreach ($predicates as $predicate) {
			$partition = $this->getPartitionForPredicate((string)$predicate);
			if ($this->mainPartition === $partition) {
				$partitionPredicates[''][] = $predicate;
			} elseif ($partition) {
				$partitionPredicates[$partition->name][] = $predicate;
			} else {
				$partitionPredicates[''][] = $predicate;
			}
		}
		return $partitionPredicates;
	}

	public function where(...$predicates) {
		return $this->andWhere(...$predicates);
	}

	public function andWhere(...$where) {
		if ($where) {
			foreach ($this->splitPredicatesByParts($where) as $alias => $predicates) {
				if (isset($this->splitQueries[$alias])) {
					// when there is a condition on a table being left-joined it starts to behave as if it's an inner join
					// since any joined column that doesn't have the left part will not match the condition
					// when there the condition is `$joinToColumn IS NULL` we instead mark the query as excluding the left half
					if ($this->splitQueries[$alias]->joinMode === PartitionQuery::JOIN_MODE_LEFT) {
						$this->splitQueries[$alias]->joinMode = PartitionQuery::JOIN_MODE_INNER;

						$column = $this->quoteHelper->quoteColumnName($this->splitQueries[$alias]->joinToColumn);
						foreach ($predicates as $predicate) {
							if ((string)$predicate === "$column IS NULL") {
								$this->splitQueries[$alias]->joinMode = PartitionQuery::JOIN_MODE_LEFT_NULL;
							} else {
								$this->splitQueries[$alias]->query->andWhere($predicate);
							}
						}
					} else {
						$this->splitQueries[$alias]->query->andWhere(...$predicates);
					}
				} else {
					parent::andWhere(...$predicates);
				}
			}
		}
		return $this;
	}


	private function getPartitionForPredicate(string $predicate): ?PartitionSplit {
		foreach ($this->partitions as $partition) {

			if (str_contains($predicate, '?')) {
				$this->hasPositionalParameter = true;
			}
			if ($partition->checkPredicateForTable($predicate)) {
				return $partition;
			}
		}
		return null;
	}

	public function update($update = null, $alias = null) {
		return parent::update($update, $alias);
	}

	public function insert($insert = null) {
		return parent::insert($insert);
	}

	public function delete($delete = null, $alias = null) {
		return parent::delete($delete, $alias);
	}

	public function setMaxResults($maxResults) {
		if ($maxResults > 0) {
			$this->limit = (int)$maxResults;
		}
		return parent::setMaxResults($maxResults);
	}

	public function setFirstResult($firstResult) {
		if ($firstResult > 0) {
			$this->offset = (int)$firstResult;
		}
		return parent::setFirstResult($firstResult);
	}

	public function executeQuery(?IDBConnection $connection = null): IResult {
		$this->applySelects();
		if ($this->splitQueries && $this->hasPositionalParameter) {
			throw new InvalidPartitionedQueryException("Partitioned queries aren't allowed to to positional arguments");
		}
		foreach ($this->splitQueries as $split) {
			$split->query->setParameters($this->getParameters(), $this->getParameterTypes());
		}
		if (count($this->splitQueries) > 0) {
			$hasNonLeftJoins = array_reduce($this->splitQueries, function (bool $hasNonLeftJoins, PartitionQuery $query) {
				return $hasNonLeftJoins || $query->joinMode !== PartitionQuery::JOIN_MODE_LEFT;
			}, false);
			if ($hasNonLeftJoins) {
				if (is_int($this->limit)) {
					throw new InvalidPartitionedQueryException('Limit is not allowed in partitioned queries');
				}
				if (is_int($this->offset)) {
					throw new InvalidPartitionedQueryException('Offset is not allowed in partitioned queries');
				}
			}
		}

		$s = $this->getSQL();
		$result = parent::executeQuery($connection);
		if (count($this->splitQueries) > 0) {
			return new PartitionedResult($this->splitQueries, $result);
		} else {
			return $result;
		}
	}

	public function executeStatement(?IDBConnection $connection = null): int {
		if (count($this->splitQueries)) {
			throw new InvalidPartitionedQueryException("Partitioning write queries isn't supported");
		}
		return parent::executeStatement($connection);
	}

	public function getSQL() {
		$this->applySelects();
		return parent::getSQL();
	}

	public function getPartitionCount(): int {
		return count($this->splitQueries) + 1;
	}

	public function hintShardKey(string $column, mixed $value, bool $overwrite = false): self {
		if (str_contains($column, '.')) {
			[$alias, $column] = explode('.', $column);
			$partition = $this->getPartition($alias);
			if ($partition) {
				$this->splitQueries[$partition->name]->query->hintShardKey($column, $value, $overwrite);
			} else {
				parent::hintShardKey($column, $value, $overwrite);
			}
		} else {
			parent::hintShardKey($column, $value, $overwrite);
		}
		return $this;
	}
}
