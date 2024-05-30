<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version23000Date20210721100600 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('authorized_groups')) {
			$table = $schema->createTable('authorized_groups');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('group_id', 'string', [
				'notnull' => true,
				'length' => 200
			]);
			$table->addColumn('class', 'string', [
				'notnull' => true,
				'length' => 200,
			]);
	
			$table->setPrimaryKey(['id']);
			$table->addIndex(['group_id'], 'admindel_groupid_idx');
			return $schema;
		}
	}
}
