<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\Attributes\DropIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[CreateTable(table: 'sharing_classmap')]
#[DropIndex(table: 'share_legacy_mapping', type: IndexType::PRIMARY)]
#[DropIndex(table: 'share_legacy_mapping', type: IndexType::UNIQUE)]
#[AddIndex(table: 'share_legacy_mapping', type: IndexType::PRIMARY)]
#[AddIndex(table: 'share_legacy_mapping', type: IndexType::INDEX)]
class Version35000Date20260813121254 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$shareLegacyMappingTable = $schema->getTable('share_legacy_mapping');
		$shareLegacyMappingTable->dropPrimaryKey();
		foreach ($shareLegacyMappingTable->getIndexes() as $index) {
			$shareLegacyMappingTable->dropIndex($index->getName());
		}

		$shareLegacyMappingTable->setPrimaryKey(['legacy_provider', 'legacy_id']);
		$shareLegacyMappingTable->addIndex(['id']);

		return null;
	}
}
