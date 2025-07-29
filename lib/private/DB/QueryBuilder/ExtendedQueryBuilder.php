<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\QueryBuilder;

use OC\DB\Exceptions\DbalException;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Base class for creating classes that extend the builtin query builder
 */
abstract class ExtendedQueryBuilder implements IQueryBuilder {
	public function __construct(
		protected IQueryBuilder $builder,
	) {
	}

	public function automaticTablePrefix($enabled) {
		$this->builder->automaticTablePrefix($enabled);
		return $this;
	}

	public function expr() {
		return $this->builder->expr();
	}

	public function func() {
		return $this->builder->func();
	}

	public function getType() {
		return $this->builder->getType();
	}

	public function getConnection() {
		return $this->builder->getConnection();
	}

	public function getState() {
		return $this->builder->getState();
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

	public function getSQL() {
		return $this->builder->getSQL();
	}

	public function setParameter($key, $value, $type = null) {
		$this->builder->setParameter($key, $value, $type);
		return $this;
	}

	public function setParameters(array $params, array $types = []) {
		$this->builder->setParameters($params, $types);
		return $this;
	}

	public function getParameters() {
		return $this->builder->getParameters();
	}

	public function getParameter($key) {
		return $this->builder->getParameter($key);
	}

	public function getParameterTypes() {
		return $this->builder->getParameterTypes();
	}

	public function getParameterType($key) {
		return $this->builder->getParameterType($key);
	}

	public function setFirstResult($firstResult) {
		$this->builder->setFirstResult($firstResult);
		return $this;
	}

	public function getFirstResult() {
		return $this->builder->getFirstResult();
	}

	public function setMaxResults($maxResults) {
		$this->builder->setMaxResults($maxResults);
		return $this;
	}

	public function getMaxResults() {
		return $this->builder->getMaxResults();
	}

	public function select(...$selects) {
		$this->builder->select(...$selects);
		return $this;
	}

	public function selectAlias($select, $alias) {
		$this->builder->selectAlias($select, $alias);
		return $this;
	}

	public function selectDistinct($select) {
		$this->builder->selectDistinct($select);
		return $this;
	}

	public function addSelect(...$select) {
		$this->builder->addSelect(...$select);
		return $this;
	}

	public function delete($delete = null, $alias = null) {
		$this->builder->delete($delete, $alias);
		return $this;
	}

	public function update($update = null, $alias = null) {
		$this->builder->update($update, $alias);
		return $this;
	}

	public function insert($insert = null) {
		$this->builder->insert($insert);
		return $this;
	}

	public function from($from, $alias = null) {
		$this->builder->from($from, $alias);
		return $this;
	}

	public function join($fromAlias, $join, $alias, $condition = null) {
		$this->builder->join($fromAlias, $join, $alias, $condition);
		return $this;
	}

	public function innerJoin($fromAlias, $join, $alias, $condition = null) {
		$this->builder->innerJoin($fromAlias, $join, $alias, $condition);
		return $this;
	}

	public function leftJoin($fromAlias, $join, $alias, $condition = null) {
		$this->builder->leftJoin($fromAlias, $join, $alias, $condition);
		return $this;
	}

	public function rightJoin($fromAlias, $join, $alias, $condition = null) {
		$this->builder->rightJoin($fromAlias, $join, $alias, $condition);
		return $this;
	}

	public function set($key, $value) {
		$this->builder->set($key, $value);
		return $this;
	}

	public function where(...$predicates) {
		$this->builder->where(...$predicates);
		return $this;
	}

	public function andWhere(...$where) {
		$this->builder->andWhere(...$where);
		return $this;
	}

	public function orWhere(...$where) {
		$this->builder->orWhere(...$where);
		return $this;
	}

	public function groupBy(...$groupBys) {
		$this->builder->groupBy(...$groupBys);
		return $this;
	}

	public function addGroupBy(...$groupBy) {
		$this->builder->addGroupBy(...$groupBy);
		return $this;
	}

	public function setValue($column, $value) {
		$this->builder->setValue($column, $value);
		return $this;
	}

	public function values(array $values) {
		$this->builder->values($values);
		return $this;
	}

	public function having(...$having) {
		$this->builder->having(...$having);
		return $this;
	}

	public function andHaving(...$having) {
		$this->builder->andHaving(...$having);
		return $this;
	}

	public function orHaving(...$having) {
		$this->builder->orHaving(...$having);
		return $this;
	}

	public function orderBy($sort, $order = null) {
		$this->builder->orderBy($sort, $order);
		return $this;
	}

	public function addOrderBy($sort, $order = null) {
		$this->builder->addOrderBy($sort, $order);
		return $this;
	}

	public function getQueryPart($queryPartName) {
		return $this->builder->getQueryPart($queryPartName);
	}

	public function getQueryParts() {
		return $this->builder->getQueryParts();
	}

	public function resetQueryParts($queryPartNames = null) {
		$this->builder->resetQueryParts($queryPartNames);
		return $this;
	}

	public function resetQueryPart($queryPartName) {
		$this->builder->resetQueryPart($queryPartName);
		return $this;
	}

	public function createNamedParameter($value, $type = self::PARAM_STR, $placeHolder = null) {
		return $this->builder->createNamedParameter($value, $type, $placeHolder);
	}

	public function createPositionalParameter($value, $type = self::PARAM_STR) {
		return $this->builder->createPositionalParameter($value, $type);
	}

	public function createParameter($name) {
		return $this->builder->createParameter($name);
	}

	public function createFunction($call) {
		return $this->builder->createFunction($call);
	}

	public function getLastInsertId(): int {
		return $this->builder->getLastInsertId();
	}

	public function getTableName($table) {
		return $this->builder->getTableName($table);
	}

	public function getColumnName($column, $tableAlias = '') {
		return $this->builder->getColumnName($column, $tableAlias);
	}

	public function executeQuery(?IDBConnection $connection = null): IResult {
		return $this->builder->executeQuery($connection);
	}

	public function executeStatement(?IDBConnection $connection = null): int {
		return $this->builder->executeStatement($connection);
	}

	public function hintShardKey(string $column, mixed $value, bool $overwrite = false): self {
		$this->builder->hintShardKey($column, $value, $overwrite);
		return $this;
	}

	public function runAcrossAllShards(): self {
		$this->builder->runAcrossAllShards();
		return $this;
	}

	public function getOutputColumns(): array {
		return $this->builder->getOutputColumns();
	}

	public function prefixTableName(string $table): string {
		return $this->builder->prefixTableName($table);
	}
}
