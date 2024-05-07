<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 *
 */
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
			$table->addIndex(['user_id', 'app_id', 'custom_id'], 'taskp_tasks_uid_appid_ident');

			return $schema;
		}

		return null;
	}
}
