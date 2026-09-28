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
 * attribute on column modification
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
#[Consumable(since: '30.0.0')]
class ModifyColumn extends ColumnMigrationAttribute {
	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function definition(): string {
		$type = is_null($this->getType()) ? '' : ' to ' . $this->getType()->value;
		return empty($this->getName())
			? 'Modification of a column from table \'' . $this->getTable() . '\'' . $type
			: 'Modification of column \'' . $this->getName() . '\' from table \'' . $this->getTable() . '\'' . $type;
	}
}
