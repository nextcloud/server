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
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[CreateTable(
	table: 'job_runs',
	columns: ['class_id', 'pid', 'status', 'duration', 'ram_peak_usage'],
	description: 'New table to store executions of background jobs',
)]
#[AddIndex(table: 'job_runs', type: IndexType::PRIMARY)]
#[AddIndex(table: 'job_runs', type: IndexType::INDEX, description: 'Allows to search on job status')]
class Version34000Date20260521110333 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('job_runs')) {
			$table = $schema->createTable('job_runs');
			$table->addColumn('run_id', Types::BIGINT, [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('class_id', Types::BIGINT, ['notnull' => true]);
			$table->addColumn('pid', Types::INTEGER, ['notnull' => true]); // Should be MEDIUMINT
			$table->addColumn('status', Types::SMALLINT, ['notnull' => true]); // Should be TINYINT
			$table->addColumn('duration', Types::INTEGER, ['notnull' => true, 'default' => 0]);
			$table->addColumn('ram_peak_usage', Types::INTEGER, ['notnull' => true, 'default' => 0]); // Should be MEDIUMINT
			$table->setPrimaryKey(['run_id']);
			$table->addIndex(['status'], 'status');
			// Makes sure there is no auto-increment in Oracle
			$schema->dropAutoincrementColumn('job_runs', 'run_id');

			return $schema;
		}

		return null;
	}
}
