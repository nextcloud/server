<?php

/**
 * SPDX-FileCopyrightText: 2019 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version25000Date20220515204012 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('share')) {
			$shareTable = $schema->getTable('share');

			if (!$shareTable->hasColumn('attributes')) {
				$shareTable->addColumn(
					'attributes',
					Types::JSON,
					[
						'default' => null,
						'notnull' => false
					]
				);
			}
		}

		return $schema;
	}
}
