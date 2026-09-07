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

final class Version1000Date20260903133238 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$legacyMappingTable = $schema->createTable('sharing_share_legacy_mapping');
		$legacyMappingTable->addColumn('id', Types::BIGINT);
		$legacyMappingTable->addColumn('legacy_provider', Types::STRING, ['length' => 128]);
		$legacyMappingTable->addColumn('legacy_id', Types::INTEGER);
		$legacyMappingTable->addColumn('last_updated', Types::BIGINT);
		$legacyMappingTable->addColumn('secret', Types::STRING, ['length' => 32]);
		$legacyMappingTable->setPrimaryKey(['legacy_provider', 'legacy_id']);
		$legacyMappingTable->addIndex(['id']);

		return $schema;
	}
}
