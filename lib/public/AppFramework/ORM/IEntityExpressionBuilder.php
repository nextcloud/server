<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\AppFramework\ORM;

use OCP\AppFramework\Attribute\Consumable;
use OCP\DB\QueryBuilder\ICompositeExpression;

/**
 * Builds WHERE predicates for `IEntityQueryBuilder` in terms of entity property names
 * rather than raw database columns; throws \InvalidArgumentException for an unknown one.
 *
 * Values are converted and bound the same way `Repository::findBy()` criteria are: a
 * `\BackedEnum` case is unwrapped to its scalar value, and the bound parameter's type is
 * derived from the property's mapped column.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IEntityExpressionBuilder {
	/** @since 36.0.0 */
	public function andX(string|ICompositeExpression ...$x): ICompositeExpression;

	/** @since 36.0.0 */
	public function orX(string|ICompositeExpression ...$x): ICompositeExpression;

	/** @since 36.0.0 */
	public function eq(string $property, int|float|string|null|\DateTime|\BackedEnum $value): string;

	/** @since 36.0.0 */
	public function neq(string $property, int|float|string|null|\DateTime|\BackedEnum $value): string;

	/** @since 36.0.0 */
	public function lt(string $property, int|float|string|\DateTime|\BackedEnum $value): string;

	/** @since 36.0.0 */
	public function lte(string $property, int|float|string|\DateTime|\BackedEnum $value): string;

	/** @since 36.0.0 */
	public function gt(string $property, int|float|string|\DateTime|\BackedEnum $value): string;

	/** @since 36.0.0 */
	public function gte(string $property, int|float|string|\DateTime|\BackedEnum $value): string;

	/** @since 36.0.0 */
	public function like(string $property, string $pattern): string;

	/** @since 36.0.0 */
	public function notLike(string $property, string $pattern): string;

	/**
	 * @param list<int|float|string|\BackedEnum> $values
	 * @since 36.0.0
	 */
	public function in(string $property, array $values): string;

	/**
	 * @param list<int|float|string|\BackedEnum> $values
	 * @since 36.0.0
	 */
	public function notIn(string $property, array $values): string;

	/** @since 36.0.0 */
	public function isNull(string $property): string;

	/** @since 36.0.0 */
	public function isNotNull(string $property): string;
}
