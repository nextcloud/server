<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Migration;

use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;

abstract class GroupMappingMigration extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $dbc,
	) {
	}

	protected function copyGroupMappingData(string $sourceTable, string $destinationTable): void {
		$insert = $this->dbc->getQueryBuilder();
		$insert->insert($destinationTable)
			->values([
				'ldap_dn' => $insert->createParameter('ldap_dn'),
				'owncloud_name' => $insert->createParameter('owncloud_name'),
				'directory_uuid' => $insert->createParameter('directory_uuid'),
				'ldap_dn_hash' => $insert->createParameter('ldap_dn_hash'),
			]);

		$query = $this->dbc->getQueryBuilder();
		$query->select('*')
			->from($sourceTable);


		$result = $query->executeQuery();
		while ($row = $result->fetch()) {
			$insert
				->setParameter('ldap_dn', $row['ldap_dn'])
				->setParameter('owncloud_name', $row['owncloud_name'])
				->setParameter('directory_uuid', $row['directory_uuid'])
				->setParameter('ldap_dn_hash', $row['ldap_dn_hash'])
			;

			$insert->executeStatement();
		}
		$result->closeCursor();
	}
}
