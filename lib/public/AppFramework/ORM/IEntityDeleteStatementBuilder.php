<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\AppFramework\ORM;

use OCP\AppFramework\Attribute\Consumable;
use OCP\DB\Exception;

/**
 * A entity-property-aware query builder for bulk deletes, obtained from the
 * protected `Repository::getDeleteStatementBuilder()` for use in a repository subclass's own
 * delete methods.
 *
 * ```php
 * // inside a Repository<T> subclass
 * public function deleteOlderThan(\DateTime $before): int {
 *     $qb = $this->getDeleteStatementBuilder();
 *     return $qb->where($qb->expr()->lt('createdAt', $before))
 *         ->executeStatement();
 * }
 * ```
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IEntityDeleteStatementBuilder extends IEntityQueryBuilder {
	/**
	 * @return int The number of rows deleted.
	 * @throws Exception
	 * @since 36.0.0
	 */
	public function executeStatement(): int;
}
