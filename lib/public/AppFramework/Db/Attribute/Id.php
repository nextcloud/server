<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Db\Attribute;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Attribute for marking a column as a primary id.
 *
 * ```php
 * #[Entity]
 * #[Table(name: 'my_entity']
 * final class MyEntity {
 *     #[Id(generatorClass: IGenerator::class)]
 *     #[Column(name: 'id', type: Types::BIGINT)]
 *     public string $id = '';
 * }
 * ```
 *
 * @since 33.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
#[Consumable(since: '33.0.0')]
final readonly class Id {
	public function __construct(
		/** @params string-class $generatorClass */
		public string $generatorClass,
	) {
	}
}
