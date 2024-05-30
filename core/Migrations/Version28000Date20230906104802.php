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
 * Introduce text2image_tasks table
 */
class Version28000Date20230906104802 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable('text2image_tasks')) {
			$table = $schema->createTable('text2image_tasks');

			$table->addColumn('id', Types::BIGINT, [
				'notnull' => true,
				'length' => 64,
				'autoincrement' => true,
			]);
			$table->addColumn('input', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('status', Types::INTEGER, [
				'notnull' => false,
				'length' => 6,
				'default' => 0,
			]);
			$table->addColumn('number_of_images', Types::INTEGER, [
				'notnull' => true,
				'default' => 1,
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
			$table->addColumn('identifier', Types::STRING, [
				'notnull' => false,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('last_updated', Types::DATETIME, [
				'notnull' => false,
			]);
			$table->addColumn('completion_expected_at', Types::DATETIME, [
				'notnull' => false,
			]);

			$table->setPrimaryKey(['id'], 't2i_tasks_id_index');
			$table->addIndex(['last_updated'], 't2i_tasks_updated');
			$table->addIndex(['status'], 't2i_tasks_status');
			$table->addIndex(['user_id', 'app_id', 'identifier'], 't2i_tasks_uid_appid_ident');

			return $schema;
		}

		return null;
	}
}
