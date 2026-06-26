<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\QueryBuilder;

use OCP\DB\IResult;
use OCP\DB\QueryBuilder\ConflictResolutionMode;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Base class for creating classes that extend the builtin query builder
 */
abstract class ExtendedQueryBuilder extends TypedQueryBuilder {
	public function __construct(
		protected IQueryBuilder $builder,
	) {
	}

	#[\Override]
	public function automaticTablePrefix($enabled) {
		$this->builder->automaticTablePrefix($enabled);
		return $this;
	}

	#[\Override]
	public function expr() {
		return $this->builder->expr();
	}

	#[\Override]
	public function func() {
		return $this->builder->func();
	}

	#[\Override]
	public function getType() {
		return $this->builder->getType();
	}

	#[\Override]
	public function getConnection() {
		return $this->builder->getConnection();
	}

	#[\Override]
	public function getState() {
		return $this->builder->getState();
	}

	#[\Override]
	public function getSQL() {
		return $this->builder->getSQL();
	}

	#[\Override]
	public function setParameter($key, $value, $type = null) {
		$this->builder->setParameter($key, $value, $type);
		return $this;
	}

	#[\Override]
	public function setParameters(array $params, array $types = []) {
		$this->builder->setParameters($params, $types);
		return $this;
	}

	#[\Override]
	public function getParameters() {
		return $this->builder->getParameters();
	}

	#[\Override]
	public function getParameter($key) {
		return $this->builder->getParameter($key);
	}

	#[\Override]
	public function getParameterTypes() {
		return $this->builder->getParameterTypes();
	}

	#[\Override]
	public function getParameterType($key) {
		return $this->builder->getParameterType($key);
	}

	#[\Override]
	public function setFirstResult($firstResult) {
		$this->builder->setFirstResult($firstResult);
		return $this;
	}

	#[\Override]
	public function getFirstResult() {
		return $this->builder->getFirstResult();
	}

	#[\Override]
	public function setMaxResults($maxResults) {
		$this->builder->setMaxResults($maxResults);
		return $this;
	}

	#[\Override]
	public function getMaxResults() {
		return $this->builder->getMaxResults();
	}

	#[\Override]
	public function select(...$selects) {
		$this->builder->select(...$selects);
		return $this;
	}

	#[\Override]
	public function selectAlias($select, $alias): self {
		$this->builder->selectAlias($select, $alias);
		return $this;
	}

	#[\Override]
	public function selectDistinct($select) {
		$this->builder->selectDistinct($select);
		return $this;
	}

	#[\Override]
	public function addSelect(...$select) {
		$this->builder->addSelect(...$select);
		return $this;
	}

	#[\Override]
	public function delete($delete = null, $alias = null) {
		$this->builder->delete($delete, $alias);
		return $this;
	}

	#[\Override]
	public function update($update = null, $alias = null) {
		$this->builder->update($update, $alias);
		return $this;
	}

	#[\Override]
	public function insert($insert = null) {
		$this->builder->insert($insert);
		return $this;
	}

	#[\Override]
	public function from($from, $alias = null) {
		$this->builder->from($from, $alias);
		return $this;
	}

	#[\Override]
	public function join($fromAlias, $join, $alias, $condition = null) {
		$this->builder->join($fromAlias, $join, $alias, $condition);
		return $this;
	}

	#[\Override]
	public function innerJoin($fromAlias, $join, $alias, $condition = null) {
		$this->builder->innerJoin($fromAlias, $join, $alias, $condition);
		return $this;
	}

	#[\Override]
	public function leftJoin($fromAlias, $join, $alias, $condition = null) {
		$this->builder->leftJoin($fromAlias, $join, $alias, $condition);
		return $this;
	}

	#[\Override]
	public function rightJoin($fromAlias, $join, $alias, $condition = null) {
		$this->builder->rightJoin($fromAlias, $join, $alias, $condition);
		return $this;
	}

	#[\Override]
	public function set($key, $value) {
		$this->builder->set($key, $value);
		return $this;
	}

	#[\Override]
	public function where(...$predicates) {
		$this->builder->where(...$predicates);
		return $this;
	}

	#[\Override]
	public function andWhere(...$where) {
		$this->builder->andWhere(...$where);
		return $this;
	}

	#[\Override]
	public function orWhere(...$where) {
		$this->builder->orWhere(...$where);
		return $this;
	}

	#[\Override]
	public function groupBy(...$groupBys) {
		$this->builder->groupBy(...$groupBys);
		return $this;
	}

	#[\Override]
	public function addGroupBy(...$groupBy) {
		$this->builder->addGroupBy(...$groupBy);
		return $this;
	}

	#[\Override]
	public function setValue($column, $value) {
		$this->builder->setValue($column, $value);
		return $this;
	}

	#[\Override]
	public function values(array $values) {
		$this->builder->values($values);
		return $this;
	}

	#[\Override]
	public function having(...$having) {
		$this->builder->having(...$having);
		return $this;
	}

	#[\Override]
	public function andHaving(...$having) {
		$this->builder->andHaving(...$having);
		return $this;
	}

	#[\Override]
	public function orHaving(...$having) {
		$this->builder->orHaving(...$having);
		return $this;
	}

	#[\Override]
	public function orderBy($sort, $order = null) {
		$this->builder->orderBy($sort, $order);
		return $this;
	}

	#[\Override]
	public function addOrderBy($sort, $order = null) {
		$this->builder->addOrderBy($sort, $order);
		return $this;
	}

	#[\Override]
	public function getQueryPart($queryPartName) {
		return $this->builder->getQueryPart($queryPartName);
	}

	#[\Override]
	public function getQueryParts() {
		return $this->builder->getQueryParts();
	}

	#[\Override]
	public function resetQueryParts($queryPartNames = null) {
		$this->builder->resetQueryParts($queryPartNames);
		return $this;
	}

	#[\Override]
	public function resetQueryPart($queryPartName) {
		$this->builder->resetQueryPart($queryPartName);
		return $this;
	}

	#[\Override]
	public function createNamedParameter($value, $type = self::PARAM_STR, $placeHolder = null) {
		return $this->builder->createNamedParameter($value, $type, $placeHolder);
	}

	#[\Override]
	public function createPositionalParameter($value, $type = self::PARAM_STR) {
		return $this->builder->createPositionalParameter($value, $type);
	}

	#[\Override]
	public function createParameter($name) {
		return $this->builder->createParameter($name);
	}

	#[\Override]
	public function createFunction($call) {
		return $this->builder->createFunction($call);
	}

	#[\Override]
	public function getLastInsertId(): int {
		return $this->builder->getLastInsertId();
	}

	#[\Override]
	public function getTableName($table) {
		return $this->builder->getTableName($table);
	}

	#[\Override]
	public function getColumnName($column, $tableAlias = '') {
		return $this->builder->getColumnName($column, $tableAlias);
	}

	#[\Override]
	public function executeQuery(?IDBConnection $connection = null): IResult {
		return $this->builder->executeQuery($connection);
	}

	#[\Override]
	public function executeStatement(?IDBConnection $connection = null): int {
		return $this->builder->executeStatement($connection);
	}

	#[\Override]
	public function hintShardKey(string $column, mixed $value, bool $overwrite = false): self {
		$this->builder->hintShardKey($column, $value, $overwrite);
		return $this;
	}

	#[\Override]
	public function runAcrossAllShards(): self {
		$this->builder->runAcrossAllShards();
		return $this;
	}

	#[\Override]
	public function getOutputColumns(): array {
		return $this->builder->getOutputColumns();
	}

	#[\Override]
	public function prefixTableName(string $table): string {
		return $this->builder->prefixTableName($table);
	}

	#[\Override]
	public function forUpdate(ConflictResolutionMode $conflictResolutionMode = ConflictResolutionMode::Ordinary): self {
		$this->builder->forUpdate($conflictResolutionMode);
		return $this;
	}
}
