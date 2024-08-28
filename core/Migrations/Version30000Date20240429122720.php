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
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 *
 */
#[CreateTable(table: 'taskprocessing_tasks')]
#[AddIndex(table: 'taskprocessing_tasks', type: IndexType::PRIMARY)]
#[AddIndex(table: 'taskprocessing_tasks', type: IndexType::INDEX)]
#[AddIndex(table: 'taskprocessing_tasks', type: IndexType::INDEX)]
#[AddIndex(table: 'taskprocessing_tasks', type: IndexType::INDEX)]
class Version30000Date20240429122720 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('taskprocessing_tasks')) {
			$table = $schema->createTable('taskprocessing_tasks');

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
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('app_id', Types::STRING, [
				'notnull' => true,
				'length' => 32,
				'default' => '',
			]);
			$table->addColumn('custom_id', Types::STRING, [
				'notnull' => false,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('last_updated', Types::INTEGER, [
				'notnull' => false,
				'length' => 4,
				'default' => 0,
				'unsigned' => true,
			]);
			$table->addColumn('completion_expected_at', Types::DATETIME, [
				'notnull' => false,
			]);
			$table->addColumn('progress', Types::FLOAT, [
				'notnull' => false,
				'default' => 0,
			]);
			$table->addColumn('error_message', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);

			$table->setPrimaryKey(['id'], 'taskp_tasks_id_index');
			$table->addIndex(['status', 'type'], 'taskp_tasks_status_type');
			$table->addIndex(['last_updated'], 'taskp_tasks_updated');
			$table->addIndex(['user_id', 'app_id', 'custom_id'], 'taskp_tasks_uid_appid_cid');

			return $schema;
		}

		return null;
	}
}
