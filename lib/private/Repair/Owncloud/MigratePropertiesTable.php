<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair\Owncloud;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class MigratePropertiesTable implements IRepairStep {

	public function __construct(
		private Connection $db,
	) {
	}

	public function getName(): string {
		return 'Migrate oc_properties table to nextcloud schema';
	}

	public function run(IOutput $output): void {
		$schema = new SchemaWrapper($this->db);
		if (!$schema->hasTable('oc_properties')) {
			$output->info('oc_properties table does not exist.');
			return;
		}

		$output->info('Update the oc_properties table schema.');
		$table = $schema->getTable('oc_properties');
		$column = $table->getColumn('propertyvalue');
		if ($column->getType() instanceof StringType) {
			$column->setType(Type::getType('text'));
			$column->setLength(null);
		}

		// Regenerate schema after migrating to it
		$this->db->migrateToSchema($schema->getWrappedSchema());
	}
}
