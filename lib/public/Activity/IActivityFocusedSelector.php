<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Activity;

use OCP\AppFramework\Attribute\Implementable;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;

/**
 * @since 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface IActivityFocusedSelector {
	/**
	 * Extend query with joins if needed, and return an array of conditions to add
	 * in the OR clause of the WHERE for filtering focused activity events
	 *
	 * @return list<string|ICompositeExpression|IQueryFunction> conditions to add in the where clause
	 * @since 35.0.0
	 */
	public function extendQuery(IQueryBuilder $query, string $userId): array;
}
