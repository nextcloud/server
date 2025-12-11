<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\QueryBuilder\Sharded;

use OC\DB\QueryBuilder\CompositeExpression;
use OC\DB\QueryBuilder\ExtendedQueryBuilder;
use OC\DB\QueryBuilder\Parameter;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * A special query builder that automatically distributes queries over multiple database shards.
 *
 * This relies on `PartitionedQueryBuilder` to handle splitting of parts of the query that touch the sharded tables
 * from the non-sharded tables. So the query build here should only either touch only sharded table or only non-sharded tables.
 *
 * Most of the logic in this class is concerned with extracting either the shard key (e.g. "storage") or primary key (e.g. "fileid")
 * from the query. The logic for actually running the query across the shards is mostly delegated to `ShardQueryRunner`.
 */
class ShardedQueryBuilder extends ExtendedQueryBuilder {
	private array $shardKeys = [];
	private array $primaryKeys = [];
	private ?ShardDefinition $shardDefinition = null;
	/** @var bool Run the query across all shards */
	private bool $allShards = false;
	private ?string $insertTable = null;
	private mixed $lastInsertId = null;
	private ?IDBConnection $lastInsertConnection = null;
	private ?int $updateShardKey = null;
	private ?int $limit = null;
	private ?int $offset = null;
	/** @var array{column: string, order: string}[] */
	private array $sortList = [];
	private string $mainTable = '';

	public function __construct(
		IQueryBuilder $builder,
		protected array $shardDefinitions,
		protected ShardConnectionManager $shardConnectionManager,
		protected AutoIncrementHandler $autoIncrementHandler,
	) {
		parent::__construct($builder);
	}

	public function getShardKeys(): array {
		return $this->getKeyValues($this->shardKeys);
	}

	public function getPrimaryKeys(): array {
		return $this->getKeyValues($this->primaryKeys);
	}

	private function getKeyValues(array $keys): array {
		$values = [];
		foreach ($keys as $key) {
			$values = array_merge($values, $this->getKeyValue($key));
		}
		return array_values(array_unique($values));
	}

	private function getKeyValue($value): array {
		if ($value instanceof Parameter) {
			$value = (string)$value;
		}
		if (is_string($value) && str_starts_with($value, ':')) {
			$param = $this->getParameter(substr($value, 1));
			if (is_array($param)) {
				return $param;
			} else {
				return [$param];
			}
		} elseif ($value !== null) {
			return [$value];
		} else {
			return [];
		}
	}

	public function where(...$predicates) {
		return $this->andWhere(...$predicates);
	}

	public function andWhere(...$where) {
		if ($where) {
			foreach ($where as $predicate) {
				$this->tryLoadShardKey($predicate);
			}
			parent::andWhere(...$where);
		}
		return $this;
	}

	private function tryLoadShardKey($predicate): void {
		if (!$this->shardDefinition) {
			return;
		}
		if ($keys = $this->tryExtractShardKeys($predicate, $this->shardDefinition->shardKey)) {
			$this->shardKeys += $keys;
		}
		if ($keys = $this->tryExtractShardKeys($predicate, $this->shardDefinition->primaryKey)) {
			$this->primaryKeys += $keys;
		}
		foreach ($this->shardDefinition->companionKeys as $companionKey) {
			if ($keys = $this->tryExtractShardKeys($predicate, $companionKey)) {
				$this->primaryKeys += $keys;
			}
		}
	}

	/**
	 * @param $predicate
	 * @param string $column
	 * @return string[]
	 */
	private function tryExtractShardKeys($predicate, string $column): array {
		if ($predicate instanceof CompositeExpression) {
			$values = [];
			foreach ($predicate->getParts() as $part) {
				$partValues = $this->tryExtractShardKeys($part, $column);
				// for OR expressions, we can only rely on the predicate if all parts contain the comparison
				if ($predicate->getType() === CompositeExpression::TYPE_OR && !$partValues) {
					return [];
				}
				$values = array_merge($values, $partValues);
			}
			return $values;
		}
		$predicate = (string)$predicate;
		// expect a condition in the form of 'alias1.column1 = placeholder' or 'alias1.column1 in placeholder'
		if (substr_count($predicate, ' ') > 2) {
			return [];
		}
		if (str_contains($predicate, ' = ')) {
			$parts = explode(' = ', $predicate);
			if ($parts[0] === "`{$column}`" || str_ends_with($parts[0], "`.`{$column}`")) {
				return [$parts[1]];
			} else {
				return [];
			}
		}

		if (str_contains($predicate, ' IN ')) {
			$parts = explode(' IN ', $predicate);
			if ($parts[0] === "`{$column}`" || str_ends_with($parts[0], "`.`{$column}`")) {
				return [trim(trim($parts[1], '('), ')')];
			} else {
				return [];
			}
		}

		return [];
	}

	public function set($key, $value) {
		if ($this->shardDefinition && $key === $this->shardDefinition->shardKey) {
			$updateShardKey = $value;
		}
		return parent::set($key, $value);
	}

	public function setValue($column, $value) {
		if ($this->shardDefinition) {
			if ($this->shardDefinition->isKey($column)) {
				$this->primaryKeys[] = $value;
			}
			if ($column === $this->shardDefinition->shardKey) {
				$this->shardKeys[] = $value;
			}
		}
		return parent::setValue($column, $value);
	}

	public function values(array $values) {
		foreach ($values as $column => $value) {
			$this->setValue($column, $value);
		}
		return $this;
	}

	private function actOnTable(string $table): void {
		$this->mainTable = $table;
		foreach ($this->shardDefinitions as $shardDefinition) {
			if ($shardDefinition->hasTable($table)) {
				$this->shardDefinition = $shardDefinition;
			}
		}
	}

	public function from($from, $alias = null) {
		if (is_string($from) && $from) {
			$this->actOnTable($from);
		}
		return parent::from($from, $alias);
	}

	public function update($update = null, $alias = null) {
		if (is_string($update) && $update) {
			$this->actOnTable($update);
		}
		return parent::update($update, $alias);
	}

	public function insert($insert = null) {
		if (is_string($insert) && $insert) {
			$this->insertTable = $insert;
			$this->actOnTable($insert);
		}
		return parent::insert($insert);
	}

	public function delete($delete = null, $alias = null) {
		if (is_string($delete) && $delete) {
			$this->actOnTable($delete);
		}
		return parent::delete($delete, $alias);
	}

	private function checkJoin(string $table): void {
		if ($this->shardDefinition) {
			if ($table === $this->mainTable) {
				throw new InvalidShardedQueryException("Sharded query on {$this->mainTable} isn't allowed to join on itself");
			}
			if (!$this->shardDefinition->hasTable($table)) {
				// this generally shouldn't happen as the partitioning logic should prevent this
				// but the check is here just in case
				throw new InvalidShardedQueryException("Sharded query on {$this->shardDefinition->table} isn't allowed to join on $table");
			}
		}
	}

	public function innerJoin($fromAlias, $join, $alias, $condition = null) {
		$this->checkJoin($join);
		return parent::innerJoin($fromAlias, $join, $alias, $condition);
	}

	public function leftJoin($fromAlias, $join, $alias, $condition = null) {
		$this->checkJoin($join);
		return parent::leftJoin($fromAlias, $join, $alias, $condition);
	}

	public function rightJoin($fromAlias, $join, $alias, $condition = null) {
		if ($this->shardDefinition) {
			throw new InvalidShardedQueryException("Sharded query on {$this->shardDefinition->table} isn't allowed to right join");
		}
		return parent::rightJoin($fromAlias, $join, $alias, $condition);
	}

	public function join($fromAlias, $join, $alias, $condition = null) {
		return $this->innerJoin($fromAlias, $join, $alias, $condition);
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
		if ($this->shardDefinition && count($this->shardDefinition->shards) > 1) {
			// we have to emulate offset
			return $this;
		} else {
			return parent::setFirstResult($firstResult);
		}
	}

	public function addOrderBy($sort, $order = null) {
		if ($order !== null && !in_array(strtoupper((string)$order), ['ASC', 'DESC'], true)) {
			$order = null;
		}

		$this->registerOrder((string)$sort, (string)($order ?? 'ASC'));
		return parent::addOrderBy($sort, $order);
	}

	public function orderBy($sort, $order = null) {
		if ($order !== null && !in_array(strtoupper((string)$order), ['ASC', 'DESC'], true)) {
			$order = null;
		}

		$this->sortList = [];
		$this->registerOrder((string)$sort, (string)($order ?? 'ASC'));
		return parent::orderBy($sort, $order);
	}

	private function registerOrder(string $column, string $order): void {
		// handle `mime + 0` and similar by just sorting on the first part of the expression
		[$column] = explode(' ', $column);
		$column = trim($column, '`');
		$this->sortList[] = [
			'column' => $column,
			'order' => strtoupper($order),
		];
	}

	public function hintShardKey(string $column, mixed $value, bool $overwrite = false): self {
		if ($overwrite) {
			$this->primaryKeys = [];
			$this->shardKeys = [];
		}
		if ($this->shardDefinition?->isKey($column)) {
			$this->primaryKeys[] = $value;
		}
		if ($column === $this->shardDefinition?->shardKey) {
			$this->shardKeys[] = $value;
		}
		return $this;
	}

	public function runAcrossAllShards(): self {
		$this->allShards = true;
		return $this;
	}

	/**
	 * @throws InvalidShardedQueryException
	 */
	public function validate(): void {
		if ($this->shardDefinition && $this->insertTable) {
			if ($this->allShards) {
				throw new InvalidShardedQueryException("Can't insert across all shards");
			}
			if (empty($this->getShardKeys())) {
				throw new InvalidShardedQueryException("Can't insert without shard key");
			}
		}
		if ($this->shardDefinition && !$this->allShards) {
			if (empty($this->getShardKeys()) && empty($this->getPrimaryKeys())) {
				throw new InvalidShardedQueryException('No shard key or primary key set for query');
			}
		}
		if ($this->shardDefinition && $this->updateShardKey) {
			$newShardKey = $this->getKeyValue($this->updateShardKey);
			$oldShardKeys = $this->getShardKeys();
			if (count($newShardKey) !== 1) {
				throw new InvalidShardedQueryException("Can't set shard key to an array");
			}
			$newShardKey = current($newShardKey);
			if (empty($oldShardKeys)) {
				throw new InvalidShardedQueryException("Can't update without shard key");
			}
			$oldShards = array_values(array_unique(array_map(function ($shardKey) {
				return $this->shardDefinition->getShardForKey((int)$shardKey);
			}, $oldShardKeys)));
			$newShard = $this->shardDefinition->getShardForKey((int)$newShardKey);
			if ($oldShards === [$newShard]) {
				throw new InvalidShardedQueryException('Update statement would move rows to a different shard');
			}
		}
	}

	public function executeQuery(?IDBConnection $connection = null): IResult {
		$this->validate();
		if ($this->shardDefinition) {
			$runner = new ShardQueryRunner($this->shardConnectionManager, $this->shardDefinition);
			return $runner->executeQuery($this->builder, $this->allShards, $this->getShardKeys(), $this->getPrimaryKeys(), $this->sortList, $this->limit, $this->offset);
		}
		return parent::executeQuery($connection);
	}

	public function executeStatement(?IDBConnection $connection = null): int {
		$this->validate();
		if ($this->shardDefinition) {
			$runner = new ShardQueryRunner($this->shardConnectionManager, $this->shardDefinition);
			if ($this->insertTable) {
				$shards = $runner->getShards($this->allShards, $this->getShardKeys());
				if (!$shards) {
					throw new InvalidShardedQueryException("Can't insert without shard key");
				}
				$count = 0;
				foreach ($shards as $shard) {
					$shardConnection = $this->shardConnectionManager->getConnection($this->shardDefinition, $shard);
					if (!$this->primaryKeys && $this->shardDefinition->table === $this->insertTable) {
						$id = $this->autoIncrementHandler->getNextPrimaryKey($this->shardDefinition, $shard);
						parent::setValue($this->shardDefinition->primaryKey, $this->createParameter('__generated_primary_key'));
						$this->setParameter('__generated_primary_key', $id, self::PARAM_INT);
						$this->lastInsertId = $id;
					}
					$count += parent::executeStatement($shardConnection);

					$this->lastInsertConnection = $shardConnection;
				}
				return $count;
			} else {
				return $runner->executeStatement($this->builder, $this->allShards, $this->getShardKeys(), $this->getPrimaryKeys());
			}
		}
		return parent::executeStatement($connection);
	}

	public function getLastInsertId(): int {
		if ($this->lastInsertId) {
			return $this->lastInsertId;
		}
		if ($this->lastInsertConnection) {
			$table = $this->builder->prefixTableName($this->insertTable);
			return $this->lastInsertConnection->lastInsertId($table);
		} else {
			return parent::getLastInsertId();
		}
	}


}
