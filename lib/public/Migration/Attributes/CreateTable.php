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
 * attribute on table creation
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
#[Consumable(since: '30.0.0')]
class CreateTable extends TableMigrationAttribute {
	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function definition(): string {
		$definition = 'Creation of new table \'' . $this->getTable() . '\'';
		$definition .= empty($this->getColumns()) ? '' : ' with columns ' . implode(', ', $this->getColumns());
		return $definition;
	}
}
