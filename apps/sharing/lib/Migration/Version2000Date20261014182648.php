<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sharing\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

final class Version2000Date20261014182648 extends SimpleMigrationStep {

	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('sharing_instance_map')) {
			$mappingTable = $schema->createTable('sharing_instance_map');
			$mappingTable->addColumn('instance_id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$mappingTable->addColumn('instance_name', Types::STRING, ['length' => 256]);
			$mappingTable->setPrimaryKey(['instance_id']);
			$mappingTable->addUniqueIndex(['instance_name']);
		} else {
			$mappingTable = $schema->getTable('sharing_instance_map');
		}

		$shareTable = $schema->getTable('sharing_share');
		if ($shareTable->hasColumn('owner_instance')) {
			$shareTable->dropColumn('owner_instance');
		}

		if (!$shareTable->hasColumn('owner_instance_id')) {
			$shareTable->addColumn('owner_instance_id', Types::INTEGER, ['notnull' => false]);
			$shareTable->addForeignKeyConstraint($mappingTable->getName(), ['owner_instance_id'], ['instance_id']);
			$shareTable->addIndex(['owner_user_id', 'owner_instance_id']);
		}

		$recipientsTable = $schema->getTable('sharing_share_recipients');
		if ($recipientsTable->hasColumn('recipient_instance')) {
			$recipientsTable->dropColumn('recipient_instance');
		}

		if ($recipientsTable->hasColumn('initiator_instance')) {
			$recipientsTable->dropColumn('initiator_instance');
		}

		if (!$recipientsTable->hasColumn('recipient_instance_id')) {
			$recipientsTable->addColumn('recipient_instance_id', Types::INTEGER, ['notnull' => false]);
			$recipientsTable->addForeignKeyConstraint($mappingTable->getName(), ['recipient_instance_id'], ['instance_id']);
			$recipientsTable->addUniqueIndex(['share_id', 'recipient_class_id', 'recipient_value', 'recipient_instance_id']);
		}

		if (!$recipientsTable->hasColumn('initiator_instance_id')) {
			$recipientsTable->addColumn('initiator_instance_id', Types::INTEGER, ['notnull' => false]);
			$recipientsTable->addForeignKeyConstraint($mappingTable->getName(), ['initiator_instance_id'], ['instance_id']);
		}

		return $schema;
	}
}
