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

final class Version2000Date20261101154320 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$mappingTable = $schema->getTable('sharing_classmap');

		$legacyMappingTable = $schema->createTable('sharing_share_legacy_mapping');
		$legacyMappingTable->addColumn('id', Types::BIGINT);
		$legacyMappingTable->addColumn('legacy_provider_class_id', Types::INTEGER);
		$legacyMappingTable->addColumn('legacy_id', Types::INTEGER);
		$legacyMappingTable->addColumn('last_updated', Types::BIGINT);
		$legacyMappingTable->addColumn('recipient_secrets', Types::TEXT);
		$legacyMappingTable->addColumn('recipient_value', Types::STRING, ['length' => 255]);
		$legacyMappingTable->setPrimaryKey(['legacy_provider_class_id', 'legacy_id']);
		$legacyMappingTable->addIndex(['id']);
		$legacyMappingTable->addForeignKeyConstraint($mappingTable->getName(), ['legacy_provider_class_id'], ['class_id']);

		return $schema;
	}
}
