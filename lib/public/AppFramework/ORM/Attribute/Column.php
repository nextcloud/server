<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\AppFramework\ORM\Attribute;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;
use OCP\DB\Schema\ColumnType;

/**
 * Attribute for mapping a property in an entity to a database column.
 *
 * ```php
 * #[Entity(name: 'my_entity']
 * final class MyEntity {
 *     #[Column(name: 'my_column', type: ColumnType::String, default: '')]
 *     public string $myColumn = '';
 * }
 * ```
 *
 * A property can be typed as a backed enum instead of a plain scalar by setting `enumType` to
 * the enum's class-string. The column itself still stores the enum's scalar backing value
 * (declare `type`/`length` accordingly), but the property is hydrated to and persisted from the
 * enum case itself:
 *
 * ```php
 * enum Status: string {
 *     case Draft = 'draft';
 *     case Published = 'published';
 * }
 *
 * #[Entity(name: 'my_entity')]
 * final class MyEntity {
 *     #[Column(name: 'status', type: ColumnType::String, length: 32, enumType: Status::class)]
 *     public Status $status = Status::Draft;
 * }
 * ```
 *
 * @since 35.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
#[Consumable(since: '35.0.0')]
final readonly class Column {
	/** @since 35.0.0 */
	public function __construct(
		/** @var non-empty-lowercase-string The name of the column in the database. */
		public string $name,
		/** @var ColumnType The type of the column in the database. */
		public ColumnType $type,
		/** @var ?int The length of the column (relevant for Types::STRING) */
		public ?int $length = null,
		/** @var bool Whether the column is nullable in the database */
		public bool $nullable = false,
		/** @var scalar|\BackedEnum|null The default value for the column in the database. */
		public mixed $default = null,
		/**
		 * @var class-string<\BackedEnum>|null The backed enum the property is typed as. The
		 *                                     column keeps storing the enum's scalar backing
		 *                                     value; only the PHP property is the enum case.
		 */
		public ?string $enumType = null,
	) {
	}
}
