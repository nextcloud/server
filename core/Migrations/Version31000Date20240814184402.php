<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\DropIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create new column and index for lazy loading in preferences for the new IUserPreferences API.
 */
#[AddColumn(table: 'preferences', name: 'lazy', type: ColumnType::SMALLINT, description: 'lazy loading to user preferences')]
#[AddColumn(table: 'preferences', name: 'type', type: ColumnType::SMALLINT, description: 'typed values to user preferences')]
#[AddColumn(table: 'preferences', name: 'flag', type: ColumnType::INTEGER, description: 'bitflag about the value')]
#[AddColumn(table: 'preferences', name: 'indexed', type: ColumnType::INTEGER, description: 'non-array value can be set as indexed')]
#[DropIndex(table: 'preferences', type: IndexType::INDEX, description: 'remove previous app/key index', notes: ['will be re-created to include \'indexed\' field'])]
#[AddIndex(table: 'preferences', type: IndexType::INDEX, description: 'new index including user+lazy')]
#[AddIndex(table: 'preferences', type: IndexType::INDEX, description: 'new index including app/key and indexed')]
class Version31000Date20240814184402 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('preferences');
		$table->addColumn('lazy', Types::SMALLINT, ['notnull' => true, 'default' => 0, 'length' => 1, 'unsigned' => true]);
		$table->addColumn('type', Types::SMALLINT, ['notnull' => true, 'default' => 0, 'unsigned' => true]);
		$table->addColumn('flags', Types::INTEGER, ['notnull' => true, 'default' => 0, 'unsigned' => true]);
		$table->addColumn('indexed', Types::STRING, ['notnull' => false, 'default' => '', 'length' => 64]);

		// removing this index from Version13000Date20170718121200
		// $table->addIndex(['appid', 'configkey'], 'preferences_app_key');
		if ($table->hasIndex('preferences_app_key')) {
			$table->dropIndex('preferences_app_key');
		}

		$table->addIndex(['userid', 'lazy'], 'prefs_uid_lazy_i');
		$table->addIndex(['appid', 'configkey', 'indexed', 'flags'], 'prefs_app_key_ind_fl_i');

		return $schema;
	}
}
