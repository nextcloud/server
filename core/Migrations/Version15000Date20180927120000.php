<?php
/**
 * @copyright Copyright (c) 2018 Bjoern Schiessle <bjoern@schiessle.org>
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

use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;

/**
 * add column for share notes
 *
 * Class Version15000Date20180927120000
 */
class Version15000Date20180927120000 extends SimpleMigrationStep {
	public function changeSchema(\OCP\Migration\IOutput $output, \Closure $schemaClosure, array $options) {

		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('cards')) {
			$table = $schema->getTable('cards');
			$table->addColumn('uid', Type::STRING, [
				'notnull' => false,
				'length' => 255
			]);
		} else {
			$table = $schema->createTable('cards');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('addressbookid', 'integer', [
				'notnull' => true,
				'default' => 0,
			]);
			$table->addColumn('carddata', 'blob', [
				'notnull' => false,
			]);
			$table->addColumn('uri', 'string', [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('lastmodified', 'bigint', [
				'notnull' => false,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('etag', 'string', [
				'notnull' => false,
				'length' => 32,
			]);
			$table->addColumn('size', 'bigint', [
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('uid', Type::STRING, [
				'notnull' => false,
				'length' => 255
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['addressbookid']);
		}

		return $schema;
	}
}
