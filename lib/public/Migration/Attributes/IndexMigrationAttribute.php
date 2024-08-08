<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use JsonSerializable;

/**
 * generic class related to migration attribute about index changes
 *
 * @since 30.0.0
 */
class IndexMigrationAttribute extends MigrationAttribute implements JsonSerializable {
	/**
	 * @param string $table name of the database table
	 * @param string $description description of the migration
	 * @param array $notes notes abour the migration/index
	 * @since 30.0.0
	 */
	public function __construct(
		string $table,
		string $description = '',
		array $notes = [],
	) {
		parent::__construct($table, $description, $notes);
	}
}
