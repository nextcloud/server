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
#[AddColumn(table: 'taskprocessing_tasks', name: 'scheduled_at', type: ColumnType::INTEGER)]
#[AddColumn(table: 'taskprocessing_tasks', name: 'started_at', type: ColumnType::INTEGER)]
#[AddColumn(table: 'taskprocessing_tasks', name: 'ended_at', type: ColumnType::INTEGER)]
class Version30000Date20240708160048 extends SimpleMigrationStep {

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

			if (!$table->hasColumn('scheduled_at')) {
				$table->addColumn('scheduled_at', Types::INTEGER, [
					'notnull' => false,
					'default' => null,
					'unsigned' => true,
				]);
			}
			if (!$table->hasColumn('started_at')) {
				$table->addColumn('started_at', Types::INTEGER, [
					'notnull' => false,
					'default' => null,
					'unsigned' => true,
				]);
			}
			if (!$table->hasColumn('ended_at')) {
				$table->addColumn('ended_at', Types::INTEGER, [
					'notnull' => false,
					'default' => null,
					'unsigned' => true,
				]);
			}

			return $schema;
		}

		return null;
	}
}
