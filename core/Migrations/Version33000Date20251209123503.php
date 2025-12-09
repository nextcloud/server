<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

class Version33000Date20251209123503 extends SimpleMigrationStep {
	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	#[Override]
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$this->connection->truncateTable('mounts', false);
	}

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('mounts');
		if (!$table->hasColumn('mount_point_hash')) {
			$table->addColumn('mount_point_hash', Types::STRING, [
				'notnull' => true,
				'length' => 32, // xxh128
			]);
			$table->dropIndex('mounts_user_root_path_index');
			$table->addUniqueIndex(['user_id', 'root_id', 'mount_point_hash'], 'mounts_user_root_path_index');
			return $schema;
		}

		return null;
	}
}
