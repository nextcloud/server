<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version14000Date20180522074438 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure,
		array $options): ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('twofactor_providers')) {
			$table = $schema->createTable('twofactor_providers');
			$table->addColumn('provider_id', 'string',
				[
					'notnull' => true,
					'length' => 32,
				]);
			$table->addColumn('uid', 'string',
				[
					'notnull' => true,
					'length' => 64,
				]);
			$table->addColumn('enabled', 'smallint',
				[
					'notnull' => true,
					'length' => 1,
				]);
			$table->setPrimaryKey(['provider_id', 'uid']);
			$table->addIndex(['uid'], 'twofactor_providers_uid');
		}

		return $schema;
	}
}
