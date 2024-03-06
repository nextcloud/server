<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Your name <your@email.com>
 *
 * @author Your name <your@email.com>
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

namespace OCA\User_LDAP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1190Date20230706134108 extends SimpleMigrationStep {
	public function __construct(
		private IDBConnection $dbc,
	) {
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('ldap_group_membership')) {
			$table = $schema->createTable('ldap_group_membership');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('groupid', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('userid', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['groupid', 'userid'], 'user_ldap_membership_unique');
			return $schema;
		} else {
			return null;
		}
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('ldap_group_members')) {
			// Old table does not exist
			return;
		}

		$output->startProgress();
		$this->copyGroupMembershipData();
		$output->finishProgress();
	}

	protected function copyGroupMembershipData(): void {
		$insert = $this->dbc->getQueryBuilder();
		$insert->insert('ldap_group_membership')
			->values([
				'userid' => $insert->createParameter('userid'),
				'groupid' => $insert->createParameter('groupid'),
			]);

		$query = $this->dbc->getQueryBuilder();
		$query->select('*')
			->from('ldap_group_members');

		$result = $query->executeQuery();
		while ($row = $result->fetch()) {
			$knownUsers = unserialize($row['owncloudusers']);
			if (!is_array($knownUsers)) {
				/* Unserialize failed or data was incorrect in database, ignore */
				continue;
			}
			$knownUsers = array_unique($knownUsers);
			foreach ($knownUsers as $knownUser) {
				try {
					$insert
						->setParameter('groupid', $row['owncloudname'])
						->setParameter('userid', $knownUser)
					;

					$insert->executeStatement();
				} catch (\OCP\DB\Exception $e) {
					/*
					 * If it fails on unique constaint violation it may just be left over value from previous half-migration
					 * If it fails on something else, ignore as well, data will be filled by background job later anyway
					 */
				}
			}
		}
		$result->closeCursor();
	}
}
