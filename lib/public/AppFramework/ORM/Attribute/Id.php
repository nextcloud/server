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
use OCP\Snowflake\ISnowflakeGenerator;

/**
 * Attribute for marking a column as a primary id.
 *
 * ```php
 * #[Entity(name: 'my_entity']
 * final class MyEntity {
 *     #[Id(generatorClass: ISnowflakeGenerator::class)]
 *     #[Column(name: 'id', type: Types::BIGINT)]
 *     public ?string $id = null;
 * }
 * ```
 *
 * @since 35.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
#[Consumable(since: '35.0.0')]
final readonly class Id {
	public function __construct(
		/** @param class-string<ISnowflakeGenerator> $generatorClass */
		public ?string $generatorClass = null,
	) {
	}
}
