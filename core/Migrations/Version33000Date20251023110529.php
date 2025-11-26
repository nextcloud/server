<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migrate away from auto-increment
 */
#[ModifyColumn(table: 'preview_locations', name: 'id', description: 'Remove auto-increment')]
#[ModifyColumn(table: 'previews', name: 'id', description: 'Remove auto-increment')]
#[ModifyColumn(table: 'preview_versions', name: 'id', description: 'Remove auto-increment')]
class Version33000Date20251023110529 extends SimpleMigrationStep {
	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if ($schema->hasTable('preview_locations')) {
			$schema->dropAutoincrementColumn('preview_locations', 'id');
		}

		if ($schema->hasTable('preview_versions')) {
			$schema->dropAutoincrementColumn('preview_versions', 'id');
		}

		if ($schema->hasTable('previews')) {
			$schema->dropAutoincrementColumn('previews', 'id');
		}

		return $schema;
	}
}
