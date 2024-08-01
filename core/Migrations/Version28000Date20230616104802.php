<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Introduce llm_tasks table
 */
class Version28000Date20230616104802 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('llm_tasks')) {
			$table = $schema->createTable('llm_tasks');

			$table->addColumn('id', Types::BIGINT, [
				'notnull' => true,
				'length' => 64,
				'autoincrement' => true,
			]);
			$table->addColumn('type', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('input', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('output', Types::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('status', Types::INTEGER, [
				'notnull' => false,
				'length' => 6,
				'default' => 0,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('app_id', Types::STRING, [
				'notnull' => true,
				'length' => 32,
				'default' => '',
			]);
			$table->addColumn('identifier', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('last_updated', 'integer', [
				'notnull' => false,
				'length' => 4,
				'default' => 0,
				'unsigned' => true,
			]);

			$table->setPrimaryKey(['id'], 'llm_tasks_id_index');
			$table->addUniqueIndex(['status', 'type'], 'llm_tasks_status_type');
			$table->addIndex(['last_updated'], 'llm_tasks_updated');

			return $schema;
		}

		return null;
	}
}
