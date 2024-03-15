<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version24000Date20211230140012 extends SimpleMigrationStep {
	public function __construct(
		protected IDBConnection $connection,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('jobs');
		if (!$table->hasColumn('argument_hash')) {
			$table->addColumn('argument_hash', Types::STRING, [
				'notnull' => false,
				'length' => 32,
			]);
			$table->addIndex(['class', 'argument_hash'], 'job_argument_hash');
			return $schema;
		}
		return null;
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$update = $this->connection->getQueryBuilder();

		$update->update('jobs')
			->set('argument_hash', $update->func()->md5('argument'));

		$update->executeStatement();
	}
}
