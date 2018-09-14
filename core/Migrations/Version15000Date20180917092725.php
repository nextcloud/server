<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Migrations;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version15000Date20180917092725 extends SimpleMigrationStep {


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('collres_collections')) {
			$table = $schema->createTable('collres_collections');

			$table->addColumn('id', Type::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);

			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('collres_resources')) {
			$table = $schema->createTable('collres_resources');

			$table->addColumn('collection_id', Type::BIGINT, [
				'notnull' => true,
			]);
			$table->addColumn('resource_type', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('resource_id', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);

			$table->addUniqueIndex(['collection_id', 'resource_type', 'resource_id'], 'collres_unique_res');
		}

		return $schema;
	}

}
