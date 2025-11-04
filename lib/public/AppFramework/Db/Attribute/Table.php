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
 * Attribute for adding table mapping information to an entity.
 *
 * ```php
 * #[Entity]
 * #[Table(name: 'my_entity']
 * final class MyEntity {
 *     #[Column(name: 'my_column', type: Types::String, default: '')]
 *     public string $myColumn = '';
 * }
 * ```
 *
 * @since 33.0.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[Consumable(since: '33.0.0')]
final readonly class Table {
	public function __construct(
		public string $name,
	) {
	}
}
