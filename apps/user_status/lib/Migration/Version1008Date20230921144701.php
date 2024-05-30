<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\UserStatus\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1008Date20230921144701 extends SimpleMigrationStep {

	public function __construct(private IDBConnection $connection) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$statusTable = $schema->getTable('user_status');
		if (!($statusTable->hasColumn('status_message_timestamp'))) {
			$statusTable->addColumn('status_message_timestamp', Types::INTEGER, [
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
				'default' => 0,
			]);
		}
		if (!$statusTable->hasIndex('user_status_mtstmp_ix')) {
			$statusTable->addIndex(['status_message_timestamp'], 'user_status_mtstmp_ix');
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$qb = $this->connection->getQueryBuilder();

		$update = $qb->update('user_status')
			->set('status_message_timestamp', 'status_timestamp');

		$update->executeStatement();
	}
}
