<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sharing\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

final class Version1000Date20260731171922 extends SimpleMigrationStep {
	/**
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @throws SchemaException
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('sharing_classmap')) {
			$table = $schema->createTable('sharing_classmap');
			$table->addColumn('class_id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('class_name', Types::STRING, ['length' => 64]);
			$table->setPrimaryKey(['class_id']);
			$table->addUniqueIndex(['class_name']);
		}

		$sourcesTable = $schema->getTable('sharing_share_sources');
		if ($sourcesTable->hasColumn('source_class')) {
			$sourcesTable->dropColumn('source_class');
			$sourcesTable->addColumn('source_class_id', Types::INTEGER);
			$sourcesTable->dropPrimaryKey();
			$sourcesTable->setPrimaryKey(['share_id', 'source_class_id', 'source_value']);
			$sourcesTable->addForeignKeyConstraint('sharing_classmap', ['source_class_id'], ['class_id']);
		}

		$recipientsTable = $schema->getTable('sharing_share_recipients');
		if ($recipientsTable->hasColumn('recipient_class')) {
			$recipientsTable->dropColumn('recipient_class');
			$recipientsTable->addColumn('recipient_class_id', Types::INTEGER);
			$recipientsTable->dropPrimaryKey();
			$recipientsTable->setPrimaryKey(['share_id', 'recipient_class_id', 'recipient_value']);
			$recipientsTable->addForeignKeyConstraint('sharing_classmap', ['recipient_class_id'], ['class_id']);
		}

		$propertiesTable = $schema->getTable('sharing_share_properties');
		if ($propertiesTable->hasColumn('property_class')) {
			$propertiesTable->dropColumn('property_class');
			$propertiesTable->addColumn('property_class_id', Types::INTEGER);
			$propertiesTable->dropPrimaryKey();
			$propertiesTable->setPrimaryKey(['share_id', 'property_class_id']);
			$propertiesTable->addForeignKeyConstraint('sharing_classmap', ['property_class_id'], ['class_id']);
		}

		$permissionsTable = $schema->getTable('sharing_share_permissions');
		if ($permissionsTable->hasColumn('permission_class')) {
			$permissionsTable->dropColumn('permission_class');
			$permissionsTable->addColumn('permission_class_id', Types::INTEGER);
			$permissionsTable->dropPrimaryKey();
			$permissionsTable->setPrimaryKey(['share_id', 'permission_class_id']);
			$permissionsTable->addForeignKeyConstraint('sharing_classmap', ['permission_class_id'], ['class_id']);
		}

		return $schema;
	}

	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
