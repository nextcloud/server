<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\DropIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Drop index fs_id_storage_size
 *
 * Added in https://github.com/nextcloud/server/pull/29118
 * Matching request changed in https://github.com/nextcloud/server/pull/50781
 */
#[DropIndex(table: 'filecache', type: IndexType::INDEX, description: 'remove index fs_id_storage_size (concurrent with PRIMARY KEY)')]
class Version31000Date20250213102442 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('filecache');

		// Index added in Version13000Date20170718121200
		if ($table->hasIndex('fs_id_storage_size')) {
			$table->dropIndex('fs_id_storage_size');
		}

		return $schema;
	}
}
