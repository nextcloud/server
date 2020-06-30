<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\Files_Sharing\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1012Date20200630192019 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('share_external')) {
			$table = $schema->createTable('share_external');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('parent', Types::INTEGER, [
				'notnull' => false,
				'length' => 4,
				'default' => -1,
			]);
			$table->addColumn('share_type', Types::INTEGER, [
				'notnull' => false,
				'length' => 4,
			]);
			$table->addColumn('remote', Types::STRING, [
				'notnull' => true,
				'length' => 512,
			]);
			$table->addColumn('remote_id', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
				'default' => -1,
			]);
			$table->addColumn('share_token', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('password', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('name', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('owner', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('user', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('mountpoint', Types::STRING, [
				'notnull' => true,
				'length' => 4000,
			]);
			$table->addColumn('mountpoint_hash', Types::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$table->addColumn('accepted', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
				'default' => 0,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['user'], 'sh_external_user');
			$table->addUniqueIndex(['user', 'mountpoint_hash'], 'sh_external_mp');
		}
		return $schema;
	}
}
