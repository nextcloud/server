<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\WorkflowEngine\Migration;

use Closure;
use Doctrine\DBAL\Schema\Table;
use OCA\WorkflowEngine\Entity\File;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version2000Date20190808074233 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('flow_checks')) {
			$table = $schema->createTable('flow_checks');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('class', Types::STRING, [
				'notnull' => true,
				'length' => 256,
				'default' => '',
			]);
			$table->addColumn('operator', Types::STRING, [
				'notnull' => true,
				'length' => 16,
				'default' => '',
			]);
			$table->addColumn('value', Types::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('hash', Types::STRING, [
				'notnull' => true,
				'length' => 32,
				'default' => '',
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['hash'], 'flow_unique_hash');
		}

		if (!$schema->hasTable('flow_operations')) {
			$table = $schema->createTable('flow_operations');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('class', Types::STRING, [
				'notnull' => true,
				'length' => 256,
				'default' => '',
			]);
			$table->addColumn('name', Types::STRING, [
				'notnull' => false,
				'length' => 256,
				'default' => '',
			]);
			$table->addColumn('checks', Types::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('operation', Types::TEXT, [
				'notnull' => false,
			]);
			$this->ensureEntityColumns($table);
			$table->setPrimaryKey(['id']);
		} else {
			$table = $schema->getTable('flow_operations');
			$this->ensureEntityColumns($table);
		}

		if (!$schema->hasTable('flow_operations_scope')) {
			$table = $schema->createTable('flow_operations_scope');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('operation_id', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
				'default' => 0,
			]);
			$table->addColumn('type', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
				'default' => 0,
			]);
			$table->addColumn('value', Types::STRING, [
				'notnull' => false,
				'length' => 64,
				'default' => '',
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['operation_id', 'type', 'value'], 'flow_unique_scope');
		}

		return $schema;
	}

	protected function ensureEntityColumns(Table $table) {
		if (!$table->hasColumn('entity')) {
			$table->addColumn('entity', Types::STRING, [
				'notnull' => true,
				'length' => 256,
				'default' => File::class,
			]);
		}
		if (!$table->hasColumn('events')) {
			$table->addColumn('events', Types::TEXT, [
				'notnull' => true,
				'default' => '[]',
			]);
		}
	}
}
