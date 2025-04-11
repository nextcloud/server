<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
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

/**
 *
 */
#[AddColumn(table: 'taskprocessing_tasks', name: 'webhook_uri', type: ColumnType::STRING)]
#[AddColumn(table: 'taskprocessing_tasks', name: 'webhook_method', type: ColumnType::STRING)]
class Version30000Date20240717111406 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('taskprocessing_tasks')) {
			$table = $schema->getTable('taskprocessing_tasks');

			$table->addColumn('webhook_uri', Types::STRING, [
				'notnull' => false,
				'default' => null,
				'length' => 4000,
			]);
			$table->addColumn('webhook_method', Types::STRING, [
				'notnull' => false,
				'default' => null,
				'length' => 64,
			]);

			return $schema;
		}

		return null;
	}
}
