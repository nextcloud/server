<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB\Schema;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Object representation of an index.
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface IIndex {
	/**
	 * Returns the name of this index.
	 *
	 * @return non-empty-string
	 * @since 35.0.0
	 */
	public function getName(): string;

	/**
	 * Returns the names of the columns this index is defined on.
	 *
	 * @return list<string>
	 * @since 35.0.0
	 */
	public function getColumns(): array;

	/**
	 * Returns whether this index is a unique index.
	 *
	 * @since 35.0.0
	 */
	public function isUnique(): bool;

	/**
	 * Returns whether this index is the primary key.
	 *
	 * @since 35.0.0
	 */
	public function isPrimary(): bool;

	/**
	 * Returns whether this index is neither unique nor the primary key.
	 *
	 * @since 35.0.0
	 */
	public function isSimpleIndex(): bool;

	/**
	 * Returns whether this index has a column at a specified position.
	 *
	 * @param non-empty-lowercase-string $name
	 * @since 35.0.0
	 */
	public function hasColumnAtPosition(string $name, int $position = 0): bool;

	/**
	 * Returns whether the given column names exactly match the columns this index spans,
	 * regardless of order.
	 *
	 * @param list<non-empty-lowercase-string> $columnNames
	 * @since 35.0.0
	 */
	public function spansColumns(array $columnNames): bool;
}
