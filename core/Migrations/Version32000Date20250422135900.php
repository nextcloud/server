<?php

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
 * Adjust name length to support 255 characters to align with operating systems.
 *
 * See also Version13000Date20170718121200
 */
#[ModifyColumn(table: 'filecache', name: 'name', description: 'adjust length to maximal supported 255 characters')]
class Version32000Date20250422135900 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('filecache');
		$column = $table->getColumn('name');
		if ($column->getLength() < 255) {
			$column->setLength(255);
		}

		return $schema;
	}
}
