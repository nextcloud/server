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

		$shareTable = $schema->createTable('sharing_share');
		$shareTable->addColumn('id', Types::BIGINT);
		$shareTable->addColumn('owner_user_id', Types::STRING, ['length' => 64]);
		$shareTable->addColumn('owner_instance', Types::STRING, ['length' => 128, 'notnull' => false]);
		$shareTable->addColumn('last_updated', Types::BIGINT);
		$shareTable->addColumn('state', Types::STRING, ['length' => 16]);
		$shareTable->setPrimaryKey(['id']);

		$sourcesTable = $schema->createTable('sharing_share_sources');
		$sourcesTable->addColumn('share_id', Types::BIGINT);
		$sourcesTable->addColumn('source_class', Types::STRING, ['length' => 64]);
		$sourcesTable->addColumn('source_value', Types::STRING, ['length' => 255]);
		$sourcesTable->setPrimaryKey(['share_id', 'source_class', 'source_value']);
		$sourcesTable->addForeignKeyConstraint($shareTable->getName(), ['share_id'], ['id'], ['onDelete' => 'CASCADE']);

		// TODO: Add possibility to mask permissions for recipients. For reshares the user may only mask permissions for their child recipients, not their self recipients
		$recipientsTable = $schema->createTable('sharing_share_recipients');
		$recipientsTable->addColumn('share_id', Types::BIGINT);
		$recipientsTable->addColumn('recipient_class', Types::STRING, ['length' => 64]);
		$recipientsTable->addColumn('recipient_value', Types::STRING, ['length' => 255]);
		$recipientsTable->addColumn('recipient_instance', Types::STRING, ['length' => 128, 'notnull' => false]);
		$recipientsTable->addColumn('recipient_secret', Types::STRING, ['length' => 32]);
		$recipientsTable->addColumn('initiator_user_id', Types::STRING, ['length' => 64]);
		$recipientsTable->addColumn('initiator_instance', Types::STRING, ['length' => 128, 'notnull' => false]);
		$recipientsTable->setPrimaryKey(['share_id', 'recipient_class', 'recipient_value']);
		$recipientsTable->addForeignKeyConstraint($shareTable->getName(), ['share_id'], ['id'], ['onDelete' => 'CASCADE']);
		// TODO: Maybe needs composite index with share_id
		$recipientsTable->addUniqueIndex(['recipient_secret']);

		$propertiesTable = $schema->createTable('sharing_share_properties');
		$propertiesTable->addColumn('share_id', Types::BIGINT);
		$propertiesTable->addColumn('property_class', Types::STRING, ['length' => 64]);
		$propertiesTable->addColumn('property_value', Types::STRING, ['length' => 1000, 'notnull' => false]);
		$propertiesTable->setPrimaryKey(['share_id', 'property_class']);
		$propertiesTable->addForeignKeyConstraint($shareTable->getName(), ['share_id'], ['id'], ['onDelete' => 'CASCADE']);

		$permissionsTable = $schema->createTable('sharing_share_permissions');
		$permissionsTable->addColumn('share_id', Types::BIGINT);
		$permissionsTable->addColumn('permission_class', Types::STRING, ['length' => 64]);
		$permissionsTable->addColumn('permission_enabled', Types::BOOLEAN);
		$permissionsTable->setPrimaryKey(['share_id', 'permission_class']);
		$permissionsTable->addForeignKeyConstraint($shareTable->getName(), ['share_id'], ['id'], ['onDelete' => 'CASCADE']);

		return $schema;
	}
}
