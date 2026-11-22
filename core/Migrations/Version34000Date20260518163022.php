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

#[CreateTable(table: 'job_classes_registry', columns: ['class_id', 'class_name'], description: 'New table to map job class name to an ID')]
#[AddIndex(table: 'job_classes_registry', type: IndexType::PRIMARY)]
#[AddIndex(table: 'job_classes_registry', type: IndexType::UNIQUE, description: 'Ensure each class is registered only once')]
class Version34000Date20260518163022 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('job_classes_registry')) {
			$table = $schema->createTable('job_classes_registry');
			$table->addColumn('class_id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('class_name', Types::STRING, ['notnull' => true, 'length' => 255]);
			$table->setPrimaryKey(['class_id']);
			$table->addUniqueConstraint(['class_name'], 'class_index');
			// Makes sure there is no auto-increment in Oracle
			$schema->dropAutoincrementColumn('job_classes_registry', 'class_id');

			return $schema;
		}

		return null;
	}
}
