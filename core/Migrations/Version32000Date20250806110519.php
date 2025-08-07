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
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 *
 */
#[AddColumn(table: 'taskprocessing_tasks', name: 'allow_cleanup', type: ColumnType::SMALLINT)]
class Version32000Date20250806110519 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('taskprocessing_tasks')) {
			$table = $schema->getTable('taskprocessing_tasks');
			if (!$table->hasColumn('allow_cleanup')) {
				$table->addColumn('allow_cleanup', Types::SMALLINT, [
					'notnull' => true,
					'default' => 1,
					'unsigned' => true,
				]);
				return $schema;
			}
		}

		return null;
	}
}
