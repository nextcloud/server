<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DB\QueryBuilder;

use OCP\AppFramework\Attribute\Consumable;
use OCP\DB\IResult;
use OCP\IDBConnection;
use Override;

/**
 * @template-covariant S of never
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
interface ITypedQueryBuilder extends IQueryBuilder {
	/**
	 * @inheritDoc
	 * @return IResult<S>
	 */
	#[Override]
	public function executeQuery(?IDBConnection $connection = null): IResult;

	/**
	 * @inheritDoc
	 * @internal This method does not work with {@see self}. Use {@see self::selectColumns()} or {@see self::selectAlias()} instead.
	 */
	#[Override]
	public function select(...$selects);

	/**
	 * @template NewS of string
	 * @param NewS ...$columns The columns to select. They are not allowed to contain table names or aliases, or asterisks. Use {@see self::selectAlias()} for that.
	 * @psalm-this-out self<S|NewS>
	 * @return $this
	 * @since 34.0.0
	 * @note Psalm has a bug that prevents inferring the correct type in chained calls: https://github.com/vimeo/psalm/issues/8803. Convert the chained calls to standalone calls or switch to PHPStan, which suffered the same bug in the past, but fixed it in 2.1.5: https://github.com/phpstan/phpstan/issues/8439
	 */
	public function selectColumns(string ...$columns): self;

	/**
	 * @inheritDoc
	 * @internal This method does not work with {@see self}. Use {@see self::selectColumnDistinct()} or {@see self::selectAlias()} instead.
	 */
	#[Override]
	public function selectDistinct($select);

	/**
	 * @template NewS of string
	 * @param NewS ...$columns The columns to select distinct. They are not allowed to contain table names or aliases, or asterisks. Use {@see self::selectAlias()} for that.
	 * @psalm-this-out self<S|NewS>
	 * @return $this
	 * @since 34.0.0
	 * @note Psalm has a bug that prevents inferring the correct type in chained calls: https://github.com/vimeo/psalm/issues/8803. Convert the chained calls to standalone calls or switch to PHPStan, which suffered the same bug in the past, but fixed it in 2.1.5: https://github.com/phpstan/phpstan/issues/8439
	 */
	public function selectColumnsDistinct(string ...$columns): self;

	/**
	 * @inheritDoc
	 * @internal This method does not work with {@see self}. Use {@see self::selectColumns()} or {@see self::selectAlias()} instead.
	 */
	#[Override]
	public function addSelect(...$select);

	/**
	 * @inheritDoc
	 * @param mixed $select
	 * @template NewS of string
	 * @param NewS $alias
	 * @psalm-this-out self<S|NewS>
	 * @return $this
	 * @note Psalm has a bug that prevents inferring the correct type in chained calls: https://github.com/vimeo/psalm/issues/8803. Convert the chained calls to standalone calls or switch to PHPStan, which suffered the same bug in the past, but fixed it in 2.1.5: https://github.com/phpstan/phpstan/issues/8439
	 */
	#[Override]
	public function selectAlias($select, $alias): self;

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function setParameter($key, $value, $type = null);

	/**
	 * @inheritDoc
	 * @return $this
	 */
	#[Override]
	public function setParameters(array $params, array $types = []);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function setFirstResult($firstResult);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function setMaxResults($maxResults);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function delete($delete = null, $alias = null);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function update($update = null, $alias = null);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function insert($insert = null);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function from($from, $alias = null);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function join($fromAlias, $join, $alias, $condition = null);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function innerJoin($fromAlias, $join, $alias, $condition = null);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function leftJoin($fromAlias, $join, $alias, $condition = null);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function rightJoin($fromAlias, $join, $alias, $condition = null);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function set($key, $value);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function where(...$predicates);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function andWhere(...$where);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function orWhere(...$where);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function groupBy(...$groupBys);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function addGroupBy(...$groupBy);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function setValue($column, $value);

	/**
	 * @inheritDoc
	 * @return $this
	 */
	#[Override]
	public function values(array $values);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function having(...$having);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function andHaving(...$having);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function orHaving(...$having);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function orderBy($sort, $order = null);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function addOrderBy($sort, $order = null);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function resetQueryParts($queryPartNames = null);

	/**
	 * @inheritDoc
	 * @return $this
	 * @psalm-suppress MissingParamType
	 */
	#[Override]
	public function resetQueryPart($queryPartName);

	/**
	 * @inheritDoc
	 * @return $this
	 */
	#[Override]
	public function hintShardKey(string $column, mixed $value, bool $overwrite = false): self;

	/**
	 * @inheritDoc
	 * @return $this
	 */
	#[Override]
	public function runAcrossAllShards(): self;

	/**
	 * @inheritDoc
	 * @return $this
	 */
	#[Override]
	public function forUpdate(ConflictResolutionMode $conflictResolutionMode = ConflictResolutionMode::Ordinary): self;
}
