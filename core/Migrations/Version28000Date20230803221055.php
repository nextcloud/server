<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Adjust textprocessing_tasks table
 */
class Version28000Date20230803221055 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$changed = false;

		if ($schema->hasTable('textprocessing_tasks')) {
			$table = $schema->getTable('textprocessing_tasks');

			$column = $table->getColumn('user_id');
			$column->setNotnull(false);

			if (!$table->hasIndex('tp_tasks_uid_appid_ident')) {
				$table->addIndex(['user_id', 'app_id', 'identifier'], 'tp_tasks_uid_appid_ident');
				$changed = true;
			}
		}

		if ($changed) {
			return $schema;
		}

		return null;
	}
}
