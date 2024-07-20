<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
