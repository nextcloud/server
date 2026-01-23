<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\QueryBuilder;

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

/**
 * Base class for creating classes that extend the builtin query builder
 */
abstract class ExtendedQueryBuilder extends TypedQueryBuilder {
	public function __construct(
		protected readonly IQueryBuilder $builder,
	) {
	}

	#[Override]
	public function automaticTablePrefix(bool $enabled): void {
		$this->builder->automaticTablePrefix($enabled);
	}

	#[Override]
	public function expr(): IExpressionBuilder {
		return $this->builder->expr();
	}

	#[Override]
	public function func(): IFunctionBuilder {
		return $this->builder->func();
	}

	#[Override]
	public function getType(): int {
		return $this->builder->getType();
	}

	#[Override]
	public function getConnection(): IDBConnection {
		return $this->builder->getConnection();
	}

	#[Override]
	public function getState(): int {
		return $this->builder->getState();
	}

	#[Override]
	public function getSQL(): string {
		return $this->builder->getSQL();
	}

	#[Override]
	public function setParameter(string|int $key, mixed $value, string|null|int $type = null): self {
		$this->builder->setParameter($key, $value, $type);
		return $this;
	}

	#[Override]
	public function setParameters(array $params, array $types = []): self {
		$this->builder->setParameters($params, $types);
		return $this;
	}

	#[Override]
	public function getParameters(): array {
		return $this->builder->getParameters();
	}

	#[Override]
	public function getParameter(int|string $key): mixed {
		return $this->builder->getParameter($key);
	}

	#[Override]
	public function getParameterTypes(): array {
		return $this->builder->getParameterTypes();
	}

	#[Override]
	public function getParameterType(int|string $key): int|string {
		return $this->builder->getParameterType($key);
	}

	#[Override]
	public function setFirstResult(int $firstResult): self {
		$this->builder->setFirstResult($firstResult);
		return $this;
	}

	#[Override]
	public function getFirstResult(): int {
		return $this->builder->getFirstResult();
	}

	#[Override]
	public function setMaxResults(?int $maxResults): self {
		$this->builder->setMaxResults($maxResults);
		return $this;
	}

	#[Override]
	public function getMaxResults(): ?int {
		return $this->builder->getMaxResults();
	}

	#[Override]
	public function select(...$selects): self {
		$this->builder->select(...$selects);
		return $this;
	}

	#[Override]
	public function selectAlias(string|IQueryFunction|IParameter|ILiteral $select, string $alias): self {
		$this->builder->selectAlias($select, $alias);
		return $this;
	}

	#[Override]
	public function selectDistinct(string|array $select): self {
		$this->builder->selectDistinct($select);
		return $this;
	}

	#[Override]
	public function addSelect(...$select): self {
		$this->builder->addSelect(...$select);
		return $this;
	}

	#[Override]
	public function delete(string $delete, ?string $alias = null): self {
		$this->builder->delete($delete, $alias);
		return $this;
	}

	#[Override]
	public function update(string $update, ?string $alias = null): self {
		$this->builder->update($update, $alias);
		return $this;
	}

	#[Override]
	public function insert(string $insert): self {
		$this->builder->insert($insert);
		return $this;
	}

	#[Override]
	public function from(string|IQueryFunction $from, ?string $alias = null): self {
		$this->builder->from($from, $alias);
		return $this;
	}

	#[Override]
	public function join(string $fromAlias, string|IQueryFunction $join, ?string $alias, string|ICompositeExpression|null $condition = null): self {
		$this->builder->join($fromAlias, $join, $alias, $condition);
		return $this;
	}

	#[Override]
	public function innerJoin(string $fromAlias, string|IQueryFunction $join, ?string $alias, string|ICompositeExpression|null $condition = null): self {
		$this->builder->innerJoin($fromAlias, $join, $alias, $condition);
		return $this;
	}

	#[Override]
	public function leftJoin(string $fromAlias, string|IQueryFunction $join, ?string $alias, string|ICompositeExpression|null $condition = null): self {
		$this->builder->leftJoin($fromAlias, $join, $alias, $condition);
		return $this;
	}

	#[Override]
	public function rightJoin(string $fromAlias, string|IQueryFunction $join, ?string $alias, string|ICompositeExpression|null $condition = null): self {
		$this->builder->rightJoin($fromAlias, $join, $alias, $condition);
		return $this;
	}

	#[Override]
	public function set(string $key, ILiteral|IParameter|IQueryFunction|string $value): self {
		$this->builder->set($key, $value);
		return $this;
	}

	#[Override]
	public function where(...$predicates): self {
		$this->builder->where(...$predicates);
		return $this;
	}

	#[Override]
	public function andWhere(...$where): self {
		$this->builder->andWhere(...$where);
		return $this;
	}

	#[Override]
	public function orWhere(...$where): self {
		$this->builder->orWhere(...$where);
		return $this;
	}

	#[Override]
	public function groupBy(...$groupBys): self {
		$this->builder->groupBy(...$groupBys);
		return $this;
	}

	#[Override]
	public function addGroupBy(...$groupBy): self {
		$this->builder->addGroupBy(...$groupBy);
		return $this;
	}

	#[Override]
	public function setValue(string $column, ILiteral|IParameter|IQueryFunction|string $value): self {
		$this->builder->setValue($column, $value);
		return $this;
	}

	#[Override]
	public function values(array $values): self {
		$this->builder->values($values);
		return $this;
	}

	#[Override]
	public function having(...$having): self {
		$this->builder->having(...$having);
		return $this;
	}

	#[Override]
	public function andHaving(...$having): self {
		$this->builder->andHaving(...$having);
		return $this;
	}

	#[Override]
	public function orHaving(...$having): self {
		$this->builder->orHaving(...$having);
		return $this;
	}

	#[Override]
	public function orderBy(string|ILiteral|IParameter|IQueryFunction $sort, ?string $order = null): self {
		$this->builder->orderBy($sort, $order);
		return $this;
	}

	#[Override]
	public function addOrderBy(string|ILiteral|IParameter|IQueryFunction $sort, ?string $order = null): self {
		$this->builder->addOrderBy($sort, $order);
		return $this;
	}

	#[Override]
	public function getQueryPart(string $queryPartName): mixed {
		return $this->builder->getQueryPart($queryPartName);
	}

	#[Override]
	public function getQueryParts(): array {
		return $this->builder->getQueryParts();
	}

	#[Override]
	public function resetQueryParts(?array $queryPartNames = null): self {
		$this->builder->resetQueryParts($queryPartNames);
		return $this;
	}

	#[Override]
	public function resetQueryPart(string $queryPartName): self {
		$this->builder->resetQueryPart($queryPartName);
		return $this;
	}

	#[Override]
	public function createNamedParameter(mixed $value, mixed $type = self::PARAM_STR, $placeHolder = null): IParameter {
		return $this->builder->createNamedParameter($value, $type, $placeHolder);
	}

	#[Override]
	public function createPositionalParameter(mixed $value, mixed $type = self::PARAM_STR): IParameter {
		return $this->builder->createPositionalParameter($value, $type);
	}

	#[Override]
	public function createParameter(string $name): IParameter {
		return $this->builder->createParameter($name);
	}

	#[Override]
	public function createFunction(string $call): IQueryFunction {
		return $this->builder->createFunction($call);
	}

	#[Override]
	public function getLastInsertId(): int {
		return $this->builder->getLastInsertId();
	}

	#[Override]
	public function getTableName(string|IQueryFunction $table): string {
		return $this->builder->getTableName($table);
	}

	#[Override]
	public function getColumnName(string $column, string $tableAlias = ''): string {
		return $this->builder->getColumnName($column, $tableAlias);
	}

	#[Override]
	public function executeQuery(?IDBConnection $connection = null): IResult {
		return $this->builder->executeQuery($connection);
	}

	#[Override]
	public function executeStatement(?IDBConnection $connection = null): int {
		return $this->builder->executeStatement($connection);
	}

	#[Override]
	public function hintShardKey(string $column, mixed $value, bool $overwrite = false): self {
		$this->builder->hintShardKey($column, $value, $overwrite);
		return $this;
	}

	#[Override]
	public function runAcrossAllShards(): self {
		$this->builder->runAcrossAllShards();
		return $this;
	}

	#[Override]
	public function getOutputColumns(): array {
		return $this->builder->getOutputColumns();
	}

	#[Override]
	public function prefixTableName(string $table): string {
		return $this->builder->prefixTableName($table);
	}

	public function forUpdate(ConflictResolutionMode $conflictResolutionMode = ConflictResolutionMode::Ordinary): self {
		$this->builder->forUpdate($conflictResolutionMode);
		return $this;
	}
}
