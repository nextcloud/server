<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\DB\QueryBuilder\Partitioned;

use OC\DB\QueryBuilder\CompositeExpression;
use OC\DB\QueryBuilder\QuoteHelper;
use OC\DB\QueryBuilder\Sharded\AutoIncrementHandler;
use OC\DB\QueryBuilder\Sharded\ShardConnectionManager;
use OC\DB\QueryBuilder\Sharded\ShardedQueryBuilder;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * A special query builder that automatically splits queries that span across multiple database partitions[1].
 *
 * This is done by inspecting the query as it's being built, and when a cross-partition join is detected,
 * the part of the query that touches the partition is split of into a different sub-query.
 * Then, when the query is executed, the results from the sub-queries are automatically merged.
 *
 * This whole process is intended to be transparent to any code using the query builder, however it does impose some extra
 * limitation for queries that work cross-partition:
 *
 * 1. Any reference to columns not in the "main table" (the table referenced by "FROM"), needs to explicitly include the
 *    table or alias the column belongs to.
 *
 *    For example:
 *    ```
 *      $query->select("mount_point", "mimetype")
 *        ->from("mounts", "m")
 *        ->innerJoin("m", "filecache", "f", $query->expr()->eq("root_id", "fileid"));
 *    ```
 *    will not work, as the query builder doesn't know that the `mimetype` column belongs to the "filecache partition".
 *    Instead, you need to do
 *    ```
 *    $query->select("mount_point", "f.mimetype")
 *        ->from("mounts", "m")
 *        ->innerJoin("m", "filecache", "f", $query->expr()->eq("m.root_id", "f.fileid"));
 *    ```
 *
 * 2. The "ON" condition for the join can only perform a comparison between both sides of the join once.
 *
 *    For example:
 *    ```
 *     $query->select("mount_point", "mimetype")
 *        ->from("mounts", "m")
 *        ->innerJoin("m", "filecache", "f", $query->expr()->andX($query->expr()->eq("m.root_id", "f.fileid"), $query->expr()->eq("m.storage_id", "f.storage")));
 *    ```
 *    will not work.
 *
 * 3. An "OR" expression in the "WHERE" cannot mention both sides of the join, this does not apply to "AND" expressions.
 *
 *     For example:
 *     ```
 *      $query->select("mount_point", "mimetype")
 *         ->from("mounts", "m")
 *         ->innerJoin("m", "filecache", "f", $query->expr()->eq("m.root_id", "f.fileid")))
 *         ->where($query->expr()->orX(
 *             $query->expr()-eq("m.user_id", $query->createNamedParameter("test"))),
 *             $query->expr()-eq("f.name", $query->createNamedParameter("test"))),
 *         ));
 *     ```
 *     will not work, but.
 *     ```
 *      $query->select("mount_point", "mimetype")
 *         ->from("mounts", "m")
 *         ->innerJoin("m", "filecache", "f", $query->expr()->eq("m.root_id", "f.fileid")))
 *         ->where($query->expr()->andX(
 *             $query->expr()-eq("m.user_id", $query->createNamedParameter("test"))),
 *             $query->expr()-eq("f.name", $query->createNamedParameter("test"))),
 *         ));
 *     ```
 *     will.
 *
 * 4. Queries that join cross-partition cannot use position parameters, only named parameters are allowed
 * 5. The "ON" condition of a join cannot contain and "OR" expression.
 * 6. Right-joins are not allowed.
 * 7. Update, delete and insert statements aren't allowed to contain cross-partition joins.
 * 8. Queries that "GROUP BY" a column from the joined partition are not allowed.
 * 9. Any `join` call needs to be made before any `where` call.
 *
 * [1]: A set of tables which can't be queried together with the rest of the tables, such as when sharding is used.
 */
class PartitionedQueryBuilder extends ShardedQueryBuilder {
	/** @var array<string, PartitionQuery> $splitQueries */
	private array $splitQueries = [];
	/** @var list<PartitionSplit> */
	private array $partitions = [];

	/** @var array{'column': string, 'alias': ?string}[] */
	private array $selects = [];
	private ?PartitionSplit $mainPartition = null;
	private bool $hasPositionalParameter = false;
	private QuoteHelper $quoteHelper;

	public function __construct(
		IQueryBuilder          $builder,
		array                  $shardDefinitions,
		ShardConnectionManager $shardConnectionManager,
		AutoIncrementHandler   $autoIncrementHandler,
	) {
		parent::__construct($builder, $shardDefinitions, $shardConnectionManager, $autoIncrementHandler);
		$this->quoteHelper = new QuoteHelper();
	}

	private function newQuery(): IQueryBuilder {
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

	private function ensureSelect(string $column): void {
		$checkColumn = $column;
		if (str_contains($checkColumn, '.')) {
			[, $checkColumn] = explode('.', $checkColumn);
		}
		foreach ($this->selects as $select) {
			if ($select['select'] === $checkColumn || $select['select'] === '*' || str_ends_with($select['select'], '.' . $checkColumn)) {
				return;
			}
		}
		$this->addSelect($column);
	}

	/**
	 * distribute the select statements to the correct partition
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
		return $this->join($fromAlias, $join, $alias, $condition, PartitionQuery::JOIN_MODE_LEFT);
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
					$joinCondition->fromColumn, $joinCondition->toColumn,
					$joinMode
				);
				$this->splitQueries[$partition->name]->query->from($join, $alias);
				$this->ensureSelect($joinCondition->fromColumn);
				$this->ensureSelect($joinCondition->toColumn);
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
					$joinCondition->fromColumn, $joinCondition->toColumn,
					$joinMode
				);
				$this->ensureSelect($joinCondition->fromColumn);
				$this->ensureSelect($joinCondition->toColumn);
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

	public function executeQuery(?IDBConnection $connection = null): IResult {
		$this->applySelects();
		if ($this->splitQueries && $this->hasPositionalParameter) {
			throw new InvalidPartitionedQueryException("Partitioned queries aren't allowed to to positional arguments");
		}
		foreach ($this->splitQueries as $split) {
			$split->query->setParameters($this->getParameters(), $this->getParameterTypes());
		}

		$s = parent::getSQL();
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
}
