<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use Attribute;

/**
 * attribute on new column creation
 *
 * @since 32.0.0
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class DataCleansing extends DataMigrationAttribute {
	/**
	 * @return string
	 * @since 32.0.0
	 */
	public function definition(): string {
		return 'Cleansing data from table \'' . $this->getTable() . '\'';
	}
}
