<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version13000Date20170705121758 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('personal_sections')) {
			$table = $schema->createTable('personal_sections');

			$table->addColumn('id', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('class', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('priority', Types::INTEGER, [
				'notnull' => true,
				'length' => 6,
				'default' => 0,
			]);

			$table->setPrimaryKey(['id'], 'personal_sections_id_index');
			$table->addUniqueIndex(['class'], 'personal_sections_class');
		}

		if (!$schema->hasTable('personal_settings')) {
			$table = $schema->createTable('personal_settings');

			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('class', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('section', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('priority', Types::INTEGER, [
				'notnull' => true,
				'length' => 6,
				'default' => 0,
			]);

			$table->setPrimaryKey(['id'], 'personal_settings_id_index');
			$table->addUniqueIndex(['class'], 'personal_settings_class');
			$table->addIndex(['section'], 'personal_settings_section');
		}

		return $schema;
	}
}
