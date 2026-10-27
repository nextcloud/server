<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\AppFramework\ORM\Attribute;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;
use OCP\Snowflake\ISnowflakeGenerator;

/**
 * Attribute for marking a column as (part of) the primary key.
 *
 * ```php
 * #[Entity(name: 'my_entity']
 * final class MyEntity {
 *     #[Id(generatorClass: ISnowflakeGenerator::class)]
 *     #[Column(name: 'id', type: ColumnType::Bigint)]
 *     public ?string $id = null;
 * }
 * ```
 *
 * Applying #[Id] to more than one property declares a composite primary key. In that case every
 * id property must have its value set before calling `insert()` (via `generatorClass`, or
 * assigned by the caller), since a composite key cannot rely on a single autoincrement column:
 *
 * ```php
 * #[Entity(name: 'my_join_entity']
 * final class MyJoinEntity {
 *     #[Id]
 *     #[Column(name: 'left_id', type: ColumnType::Bigint)]
 *     public int $leftId;
 *
 *     #[Id]
 *     #[Column(name: 'right_id', type: ColumnType::Bigint)]
 *     public int $rightId;
 * }
 * ```
 *
 * @since 35.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
#[Consumable(since: '35.0.0')]
final readonly class Id {
	/** @since 35.0.0 */
	public function __construct(
		/** @var class-string<ISnowflakeGenerator>|null */
		public ?string $generatorClass = null,
	) {
	}
}
