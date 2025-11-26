<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;

/**
 * attribute on index drop
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
#[Consumable(since: '30.0.0')]
class DropIndex extends IndexMigrationAttribute {
	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function definition(): string {
		return 'Deletion of an index from table \'' . $this->getTable() . '\'';
	}
}
