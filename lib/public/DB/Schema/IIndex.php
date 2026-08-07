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
	 * Checks if this index exactly spans the given column names in the correct order.
	 *
	 * @param list<string> $columnNames
	 * @since 35.0.0
	 */
	public function spansColumns(array $columnNames): bool;

	/**
	 * Checks whether the given column is at the given position within this index.
	 *
	 * @since 35.0.0
	 */
	public function hasColumnAtPosition(string $name, int $pos = 0): bool;

	/**
	 * Returns the platform specific flags for this index.
	 *
	 * @return list<string>
	 * @since 35.0.0
	 */
	public function getFlags(): array;

	/**
	 * Returns whether this index has the given platform specific flag.
	 *
	 * @since 35.0.0
	 */
	public function hasFlag(string $flag): bool;

	/**
	 * Returns whether this index has the given platform specific option.
	 *
	 * @since 35.0.0
	 */
	public function hasOption(string $name): bool;

	/**
	 * Returns the given platform specific option.
	 *
	 * @since 35.0.0
	 */
	public function getOption(string $name): mixed;

	/**
	 * Returns all platform specific options.
	 *
	 * @return array<string, mixed>
	 * @since 35.0.0
	 */
	public function getOptions(): array;
}
