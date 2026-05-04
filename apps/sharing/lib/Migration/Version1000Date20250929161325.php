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

		$shareTable = $schema->createTable('sharing_share');
		$shareTable->addColumn('id', Types::BIGINT);
		$shareTable->addColumn('owner', Types::TEXT);
		$shareTable->addColumn('last_updated', Types::BIGINT);
		$shareTable->addColumn('state', Types::TEXT);
		$shareTable->setPrimaryKey(['id']);

		$sourcesTable = $schema->createTable('sharing_share_sources');
		$sourcesTable->addColumn('id', Types::BIGINT);
		$sourcesTable->addColumn('source_class', Types::TEXT);
		$sourcesTable->addColumn('source_value', Types::TEXT);
		$sourcesTable->setPrimaryKey(['id', 'source_class', 'source_value']);
		$sourcesTable->addForeignKeyConstraint($shareTable->getName(), ['id'], ['id'], ['onDelete' => 'CASCADE']);

		$recipientsTable = $schema->createTable('sharing_share_recipients');
		$recipientsTable->addColumn('id', Types::BIGINT);
		$recipientsTable->addColumn('recipient_class', Types::TEXT);
		$recipientsTable->addColumn('recipient_value', Types::TEXT);
		$recipientsTable->setPrimaryKey(['id', 'recipient_class', 'recipient_value']);
		$recipientsTable->addForeignKeyConstraint($shareTable->getName(), ['id'], ['id'], ['onDelete' => 'CASCADE']);

		$propertiesTable = $schema->createTable('sharing_share_properties');
		$propertiesTable->addColumn('id', Types::BIGINT);
		$propertiesTable->addColumn('property_class', Types::TEXT);
		$propertiesTable->addColumn('property_value', Types::TEXT, ['notnull' => false]);
		$propertiesTable->setPrimaryKey(['id', 'property_class']);
		$propertiesTable->addForeignKeyConstraint($shareTable->getName(), ['id'], ['id'], ['onDelete' => 'CASCADE']);

		$permissionsTable = $schema->createTable('sharing_share_permissions');
		$permissionsTable->addColumn('id', Types::BIGINT);
		$permissionsTable->addColumn('permission_class', Types::TEXT);
		$permissionsTable->addColumn('permission_enabled', Types::BOOLEAN);
		$permissionsTable->setPrimaryKey(['id', 'permission_class']);
		$permissionsTable->addForeignKeyConstraint($shareTable->getName(), ['id'], ['id'], ['onDelete' => 'CASCADE']);

		return $schema;
	}
}
