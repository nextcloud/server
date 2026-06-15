<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\ORM\Attribute;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;
use OCP\DB\Types;

/**
 * Attribute for mapping a property in an entity to a database column.
 *
 * ```php
 * #[Entity(name: 'my_entity']
 * final class MyEntity {
 *     #[Column(name: 'my_column', type: Types::String, default: '')]
 *     public string $myColumn = '';
 * }
 * ```
 *
 * @since 35.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
#[Consumable(since: '35.0.0')]
final readonly class Column {
	public function __construct(
		/** @param non-empty-string $name The name of the column in the database. */
		public string $name,
		/** @param Types::* $type The type of the column in the database. */
		public string $type,
		/** @param ?int $length The length of the column (relevant for Types::STRING) */
		public ?int $length = null,
		/** @param bool $nullable Whether the column is nullable in the database */
		public bool $nullable = false,
		/** @param mixed $default The default value for the column in the database. */
		public mixed $default = null,
	) {
	}
}
