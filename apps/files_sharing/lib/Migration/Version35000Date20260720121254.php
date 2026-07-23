<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

class Version35000Date20260720121254 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$shareLegacyMappingTable = $schema->createTable('share_legacy_mapping');
		$shareLegacyMappingTable->addColumn('id', Types::BIGINT);
		$shareLegacyMappingTable->addColumn('legacy_provider', Types::STRING, ['length' => 128]);
		$shareLegacyMappingTable->addColumn('legacy_id', Types::STRING, ['length' => 128]);
		$shareLegacyMappingTable->setPrimaryKey(['id']);
		$shareLegacyMappingTable->addUniqueIndex(['legacy_provider', 'legacy_id']);

		return null;
	}
}
