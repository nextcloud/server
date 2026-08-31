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

final class Version1000Date20260826122228 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$recipientsTable = $schema->getTable('sharing_share_recipients');
		$mappingTable = $schema->getTable('sharing_classmap');

		$recipientPermissionsTable = $schema->createTable('sharing_share_recipient_permissions');
		$recipientPermissionsTable->addColumn('recipient_id', Types::BIGINT);
		$recipientPermissionsTable->addColumn('permission_class_id', Types::INTEGER);
		$recipientPermissionsTable->addColumn('permission_enabled', Types::BOOLEAN);
		$recipientPermissionsTable->setPrimaryKey(['recipient_id', 'permission_class_id']);
		$recipientPermissionsTable->addForeignKeyConstraint($recipientsTable->getName(), ['recipient_id'], ['id'], ['onDelete' => 'CASCADE']);
		$recipientPermissionsTable->addForeignKeyConstraint($mappingTable->getName(), ['permission_class_id'], ['class_id']);

		return $schema;
	}
}
