<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\AppFramework\ORM\Attribute;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Attribute for marking a class as an entity mapped to some database table.
 *
 * ```php
 * #[Entity(name: 'my_entity')]
 * final class MyEntity {
 *     #[Column(name: 'my_column', type: ColumnType::String, default: '')]
 *     public string $myColumn = '';
 * }
 * ```
 *
 * @since 35.0.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[Consumable(since: '35.0.0')]
final readonly class Entity {
	/** @since 35.0.0 */
	public function __construct(
		/** @var non-empty-string The name of the table in the database. */
		public string $name,
	) {
	}
}
