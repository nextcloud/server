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
	 * @since 34.0.0
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
	 * @since 34.0.0
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
	 * @psalm-suppress LessSpecificImplementedReturnType
	 */
	#[Override]
	public function selectAlias($select, $alias): self;
}
