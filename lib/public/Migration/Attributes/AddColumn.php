<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use Attribute;

/**
 * attribute on new column creation
 *
 * @since 30.0.0
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class AddColumn extends ColumnMigrationAttribute {
	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function definition(): string {
		$type = is_null($this->getType()) ? '' : ' (' . $this->getType()->value . ')';
		return empty($this->getName()) ?
			'Addition of a new column' . $type . ' to table \'' . $this->getTable() . '\''
			: 'Addition of column \'' . $this->getName() . '\'' . $type . ' to table \'' . $this->getTable() . '\'';
	}
}
