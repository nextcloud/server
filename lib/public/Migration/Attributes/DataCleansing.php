<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;

/**
 * attribute on new column creation
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
#[Consumable(since: '32.0.0')]
class DataCleansing extends DataMigrationAttribute {
	/**
	 * @return string
	 * @since 32.0.0
	 */
	public function definition(): string {
		return 'Cleansing data from table \'' . $this->getTable() . '\'';
	}
}
