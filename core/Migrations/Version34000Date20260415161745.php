<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
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
use Override;

#[AddIndex(table: 'mounts', type: IndexType::UNIQUE)]
#[DropIndex(table: 'mounts', type: IndexType::UNIQUE)]
class Version34000Date20260415161745 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$changed = false;
		if ($schema->hasTable('mounts')) {
			$table = $schema->getTable('mounts');

			if ($table->hasIndex('mounts_user_root_path_index')) {
				$table->dropIndex('mounts_user_root_path_index');
				$changed = true;
			}
			if (!$table->hasIndex('mounts_user_path_root_index')) {
				$table->addUniqueIndex(['user_id', 'mount_point_hash', 'root_id'], 'mounts_user_path_root_index');
				$changed = true;
			}
		}

		return $changed ? $schema : null;
	}
}
