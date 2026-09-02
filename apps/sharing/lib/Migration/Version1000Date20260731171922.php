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
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\Attributes\DropColumn;
use OCP\Migration\Attributes\DropIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[CreateTable(table: 'sharing_classmap')]
#[DropColumn(table: 'sharing_share_sources', name: 'source_class')]
#[DropColumn(table: 'sharing_share_recipients', name: 'recipient_class')]
#[DropColumn(table: 'sharing_share_properties', name: 'property_class')]
#[DropColumn(table: 'sharing_share_permissions', name: 'permission_class')]
#[AddColumn(table: 'sharing_share_sources', name: 'source_class_id')]
#[AddColumn(table: 'sharing_share_recipients', name: 'recipient_class_id')]
#[AddColumn(table: 'sharing_share_properties', name: 'property_class_id')]
#[AddColumn(table: 'sharing_share_permissions', name: 'permission_class_id')]
#[DropIndex(table: 'sharing_share_sources', type: IndexType::PRIMARY)]
#[DropIndex(table: 'sharing_share_recipients', type: IndexType::PRIMARY)]
#[DropIndex(table: 'sharing_share_properties', type: IndexType::PRIMARY)]
#[DropIndex(table: 'sharing_share_permissions', type: IndexType::PRIMARY)]
#[AddIndex(table: 'sharing_share_sources', type: IndexType::PRIMARY)]
#[AddIndex(table: 'sharing_share_recipients', type: IndexType::PRIMARY)]
#[AddIndex(table: 'sharing_share_properties', type: IndexType::PRIMARY)]
#[AddIndex(table: 'sharing_share_permissions', type: IndexType::PRIMARY)]
final class Version1000Date20260731171922 extends SimpleMigrationStep {
	/**
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @throws SchemaException
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('sharing_classmap')) {
			$mappingTable = $schema->createTable('sharing_classmap');
			$mappingTable->addColumn('class_id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$mappingTable->addColumn('class_name', Types::STRING, ['length' => 64]);
			$mappingTable->setPrimaryKey(['class_id']);
			$mappingTable->addUniqueIndex(['class_name']);
		} else {
			$mappingTable = $schema->getTable('sharing_classmap');
		}

		$sourcesTable = $schema->getTable('sharing_share_sources');
		if ($sourcesTable->hasColumn('source_class')) {
			$sourcesTable->dropColumn('source_class');
			$sourcesTable->addColumn('source_class_id', Types::INTEGER);
			$sourcesTable->dropPrimaryKey();
			$sourcesTable->setPrimaryKey(['share_id', 'source_class_id', 'source_value']);
			$sourcesTable->addForeignKeyConstraint($mappingTable->getName(), ['source_class_id'], ['class_id']);
		}

		$recipientsTable = $schema->getTable('sharing_share_recipients');
		if ($recipientsTable->hasColumn('recipient_class')) {
			$recipientsTable->dropColumn('recipient_class');
			$recipientsTable->addColumn('recipient_class_id', Types::INTEGER);
			$recipientsTable->dropPrimaryKey();
			$recipientsTable->setPrimaryKey(['share_id', 'recipient_class_id', 'recipient_value']);
			$recipientsTable->addForeignKeyConstraint($mappingTable->getName(), ['recipient_class_id'], ['class_id']);
		}

		$propertiesTable = $schema->getTable('sharing_share_properties');
		if ($propertiesTable->hasColumn('property_class')) {
			$propertiesTable->dropColumn('property_class');
			$propertiesTable->addColumn('property_class_id', Types::INTEGER);
			$propertiesTable->dropPrimaryKey();
			$propertiesTable->setPrimaryKey(['share_id', 'property_class_id']);
			$propertiesTable->addForeignKeyConstraint($mappingTable->getName(), ['property_class_id'], ['class_id']);
		}

		$permissionsTable = $schema->getTable('sharing_share_permissions');
		if ($permissionsTable->hasColumn('permission_class')) {
			$permissionsTable->dropColumn('permission_class');
			$permissionsTable->addColumn('permission_class_id', Types::INTEGER);
			$permissionsTable->dropPrimaryKey();
			$permissionsTable->setPrimaryKey(['share_id', 'permission_class_id']);
			$permissionsTable->addForeignKeyConstraint($mappingTable->getName(), ['permission_class_id'], ['class_id']);
		}

		return $schema;
	}

	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
