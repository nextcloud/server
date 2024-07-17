<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class CreateTable extends TableMigrationAttribute {
	public function definition(): string {
		$definition = empty($this->getTable()) ? 'Creation of a new table' : 'Creation of new table \'' . $this->getTable() . '\'';
		$definition .= empty($this->getColumns()) ? '' : ' with columns ' . implode(', ', $this->getColumns());
		return $definition;
	}
}
