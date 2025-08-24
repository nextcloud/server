<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\SystemTags\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Add color column to systemtag
 */
#[AddColumn(table: 'systemtag', name: 'color', type: ColumnType::STRING, description: 'Adding color for systemtag table')]
class Version31000Date20241114171300 extends SimpleMigrationStep {

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('systemtag')) {
			$table = $schema->getTable('systemtag');

			if (!$table->hasColumn('color')) {
				$table->addColumn('color', Types::STRING, [
					'notnull' => false,
					'length' => 6,
				]);
			}
		}

		return $schema;
	}
}
