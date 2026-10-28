<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[AddColumn(table: 'authtoken', name: 'login_id', type: ColumnType::BIGINT, description: 'id of the first token of the login a renewed or copied session token belongs to')]
class Version36000Date20261027192721 extends SimpleMigrationStep {

	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$table = $schema->getTable('authtoken');
		if ($table->hasColumn('login_id')) {
			return null;
		}

		// Holds values of `id`, which is an integer or, after `occ db:convert-filecache-bigint`, a bigint
		$table->addColumn('login_id', Types::BIGINT, [
			'notnull' => false,
			'unsigned' => true,
		]);
		return $schema;
	}
}
