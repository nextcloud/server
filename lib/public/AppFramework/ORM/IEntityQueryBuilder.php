<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\AppFramework\ORM;

use OCP\AppFramework\Attribute\Consumable;
use OCP\DB\QueryBuilder\ICompositeExpression;

/**
 * The common, entity-property-aware WHERE-building surface shared by
 * `ISelectEntityQueryBuilder` and `IEntityDeleteStatementBuilder`.
 *
 * Conditions are expressed in terms of entity property names, never raw database columns,
 * and throw \InvalidArgumentException for an unknown one; see `expr()` for building the
 * predicates passed to `where()`/`andWhere()`/`orWhere()`.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IEntityQueryBuilder {
	/** @since 36.0.0 */
	public function expr(): IEntityExpressionBuilder;

	/** @since 36.0.0 */
	public function where(string|ICompositeExpression ...$predicates): static;

	/** @since 36.0.0 */
	public function andWhere(string|ICompositeExpression ...$predicates): static;

	/** @since 36.0.0 */
	public function orWhere(string|ICompositeExpression ...$predicates): static;

	/** @since 36.0.0 */
	public function setMaxResults(int $maxResults): static;
}
