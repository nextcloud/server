<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\DropIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Replace user mounts index with a version with a unique constraint.
 */
#[DropIndex(table: 'mounts', type: IndexType::INDEX, description: 'remove non-unique user mounts index', notes: ['will be re-created to make it unique'])]
#[AddIndex(table: 'mounts', type: IndexType::INDEX, description: 'new unique index for user mounts')]
class Version32000Date20250721125100 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('mounts');
		// replacing index with unique version, to avoid duplicate rows
		if ($table->hasIndex('mounts_user_root_path_index')) {
			$table->dropIndex('mounts_user_root_path_index');
			$table->addUniqueIndex(
				['user_id', 'root_id', 'mount_point'],
				'mounts_user_root_path_index',
				['lengths' => [null, null, 128]]
			);
		}

		return $schema;
	}
}
