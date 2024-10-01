<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use Attribute;

/**
 * attribute on index creation
 *
 * @since 30.0.0
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class AddIndex extends IndexMigrationAttribute {
	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function definition(): string {
		$type = is_null($this->getType()) ? '' : ' (' . $this->getType()->value . ')';
		return 'Addition of a new index' . $type . ' to table \'' . $this->getTable() . '\'';
	}
}
