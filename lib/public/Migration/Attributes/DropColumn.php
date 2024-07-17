<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class DropColumn extends ColumnMigrationAttribute {
	public function definition(): string {
		$table = empty($this->getTable()) ? '' : ' from table \'' . $this->getTable() . '\'';
		return empty($this->getName()) ?
			'Deletion of a column' . $table
			: 'Deletion of column \'' . $this->getName() . '\'' . $table;
	}
}
