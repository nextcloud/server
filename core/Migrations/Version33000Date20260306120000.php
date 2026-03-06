<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Increase bucket_name column length to 63 to match AWS bucket naming rules
 */
#[ModifyColumn(table: 'preview_locations', name: 'bucket_name', description: 'Increase column length to 63 to match AWS bucket naming rules')]
class Version33000Date20260306120000 extends SimpleMigrationStep {

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('preview_locations')) {
			$table = $schema->getTable('preview_locations');
			$column = $table->getColumn('bucket_name');

			if ($column->getLength() < 63) {
				$column->setLength(63);
			}
		}

		return $schema;
	}
}
