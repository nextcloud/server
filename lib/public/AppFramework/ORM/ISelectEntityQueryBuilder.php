<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\AppFramework\ORM;

use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * A entity-property-aware query builder, obtained from the protected
 * `Repository::getSelectQueryBuilder()` for use in a repository subclass's own finder methods.
 *
 * ```php
 * // inside a Repository<T> subclass
 * public function findRecent(\DateTime $since, int $limit): \Generator {
 *     $qb = $this->getSelectQueryBuilder();
 *     return $qb->where($qb->expr()->gt('createdAt', $since))
 *         ->orderBy('createdAt')
 *         ->setMaxResults($limit)
 *         ->toIterable();
 * }
 * ```
 *
 * @template T as object
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface ISelectEntityQueryBuilder extends IEntityQueryBuilder {
	/** @since 36.0.0 */
	public function orderBy(string $property, \SortDirection $order = \SortDirection::Ascending): static;

	/** @since 36.0.0 */
	public function addOrderBy(string $property, \SortDirection $order = \SortDirection::Ascending): static;

	/** @since 36.0.0 */
	public function setFirstResult(int $firstResult): static;

	/**
	 * @return \Generator<T>
	 * @since 36.0.0
	 */
	public function toIterable(): \Generator;

	/**
	 * @return array<T>
	 * @since 36.0.0
	 */
	public function getArrayResult(): array;

	/**
	 * @return T
	 * @throws DoesNotExistException if no entity matches
	 * @throws MultipleObjectsReturnedException if more than one entity matches
	 * @since 36.0.0
	 */
	public function getSingleResult(): object;

	/**
	 * @return T|null
	 * @throws MultipleObjectsReturnedException if more than one entity matches
	 * @since 36.0.0
	 */
	public function getOneOrNullResult(): ?object;
}
