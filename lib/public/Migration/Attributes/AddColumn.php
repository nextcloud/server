<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class AddColumn extends ColumnMigrationAttribute {
	public function definition(): string {
		$type = is_null($this->getType()) ? '' : ' (' . $this->getType()->value . ')';
		$table = empty($this->getTable()) ? '' : ' to table \'' . $this->getTable() . '\'';
		return empty($this->getName()) ?
			'Addition of a new column' . $type . $table
			: 'Addition of column \'' . $this->getName() . '\'' . $type . $table;
	}
}
