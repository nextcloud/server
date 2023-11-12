<?php

declare(strict_types=1);
/**
 * @copyright 2023 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version28000Date20231004103301 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$updated = false;

		if (!$schema->hasTable('files_metadata')) {
			$table = $schema->createTable('files_metadata');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('file_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('json', Types::TEXT);
			$table->addColumn('sync_token', Types::STRING, [
				'length' => 15,
			]);
			$table->addColumn('last_update', Types::DATETIME);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['file_id'], 'files_meta_fileid');
			$updated = true;
		}

		if (!$schema->hasTable('files_metadata_index')) {
			$table = $schema->createTable('files_metadata_index');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('file_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('meta_key', Types::STRING, [
				'notnull' => false,
				'length' => 31,
			]);
			$table->addColumn('meta_value_string', Types::STRING, [
				'notnull' => false,
				'length' => 63,
			]);
			$table->addColumn('meta_value_int', Types::BIGINT, [
				'notnull' => false,
				'length' => 11,
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['file_id', 'meta_key', 'meta_value_string'], 'f_meta_index');
			$table->addIndex(['file_id', 'meta_key', 'meta_value_int'], 'f_meta_index_i');
			$updated = true;
		}

		if (!$updated) {
			return null;
		}

		return $schema;
	}
}
