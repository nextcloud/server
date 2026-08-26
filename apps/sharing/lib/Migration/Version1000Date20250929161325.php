<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

final class Version1000Date20250929161325 extends SimpleMigrationStep {
	/**
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @throws SchemaException
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		// TODO: Check indexes

		$mappingTable = $schema->createTable('sharing_classmap');
		$mappingTable->addColumn('class_id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
		]);
		$mappingTable->addColumn('class_name', Types::STRING, ['length' => 64]);
		$mappingTable->setPrimaryKey(['class_id']);
		$mappingTable->addUniqueIndex(['class_name']);

		$shareTable = $schema->createTable('sharing_share');
		$shareTable->addColumn('id', Types::BIGINT);
		$shareTable->addColumn('owner_user_id', Types::STRING, ['length' => 64]);
		$shareTable->addColumn('owner_instance', Types::STRING, ['length' => 128, 'notnull' => false]);
		$shareTable->addColumn('last_updated', Types::BIGINT);
		$shareTable->addColumn('state', Types::STRING, ['length' => 16]);
		$shareTable->setPrimaryKey(['id']);

		$sourcesTable = $schema->createTable('sharing_share_sources');
		$sourcesTable->addColumn('share_id', Types::BIGINT);
		$sourcesTable->addColumn('source_class_id', Types::INTEGER);
		$sourcesTable->addColumn('source_value', Types::STRING, ['length' => 255]);
		$sourcesTable->setPrimaryKey(['share_id', 'source_class_id', 'source_value']);
		$sourcesTable->addForeignKeyConstraint($shareTable->getName(), ['share_id'], ['id'], ['onDelete' => 'CASCADE']);
		$sourcesTable->addForeignKeyConstraint($mappingTable->getName(), ['source_class_id'], ['class_id']);

		// TODO: Add possibility to mask permissions for recipients. For reshares the user may only mask permissions for their child recipients, not their self recipients
		$recipientsTable = $schema->createTable('sharing_share_recipients');
		$recipientsTable->addColumn('id', Types::BIGINT);
		$recipientsTable->addColumn('share_id', Types::BIGINT);
		$recipientsTable->addColumn('recipient_class_id', Types::INTEGER);
		$recipientsTable->addColumn('recipient_value', Types::STRING, ['length' => 255]);
		$recipientsTable->addColumn('recipient_instance', Types::STRING, ['length' => 128, 'notnull' => false]);
		$recipientsTable->addColumn('recipient_secret', Types::STRING, ['length' => 32]);
		$recipientsTable->addColumn('initiator_user_id', Types::STRING, ['length' => 64]);
		$recipientsTable->addColumn('initiator_instance', Types::STRING, ['length' => 128, 'notnull' => false]);
		$recipientsTable->setPrimaryKey(['id']);
		$recipientsTable->addUniqueIndex(['share_id', 'recipient_class_id', 'recipient_value']);
		$recipientsTable->addForeignKeyConstraint($shareTable->getName(), ['share_id'], ['id'], ['onDelete' => 'CASCADE']);
		// TODO: Maybe needs composite index with share_id
		$recipientsTable->addUniqueIndex(['recipient_secret']);
		$recipientsTable->addForeignKeyConstraint($mappingTable->getName(), ['recipient_class_id'], ['class_id']);

		$propertiesTable = $schema->createTable('sharing_share_properties');
		$propertiesTable->addColumn('share_id', Types::BIGINT);
		$propertiesTable->addColumn('property_class_id', Types::INTEGER);
		$propertiesTable->addColumn('property_value', Types::STRING, ['length' => 1000, 'notnull' => false]);
		$propertiesTable->setPrimaryKey(['share_id', 'property_class_id']);
		$propertiesTable->addForeignKeyConstraint($shareTable->getName(), ['share_id'], ['id'], ['onDelete' => 'CASCADE']);
		$propertiesTable->addForeignKeyConstraint($mappingTable->getName(), ['property_class_id'], ['class_id']);

		$permissionsTable = $schema->createTable('sharing_share_permissions');
		$permissionsTable->addColumn('share_id', Types::BIGINT);
		$permissionsTable->addColumn('permission_class_id', Types::INTEGER);
		$permissionsTable->addColumn('permission_enabled', Types::BOOLEAN);
		$permissionsTable->setPrimaryKey(['share_id', 'permission_class_id']);
		$permissionsTable->addForeignKeyConstraint($shareTable->getName(), ['share_id'], ['id'], ['onDelete' => 'CASCADE']);
		$permissionsTable->addForeignKeyConstraint($mappingTable->getName(), ['permission_class_id'], ['class_id']);

		return $schema;
	}
}
