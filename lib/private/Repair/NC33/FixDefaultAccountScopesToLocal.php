<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC33;

use OCP\Accounts\IAccountManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Fix default account property scopes from federated to local.
 *
 * Previously, properties like displayname, email, avatar, and pronouns
 * defaulted to SCOPE_FEDERATED, which exposed user information to
 * federated servers without explicit user consent.
 *
 * This repair step changes those properties to SCOPE_LOCAL for existing
 * users who still have the old default federated scope on properties
 * that were previously defaulting to federated.
 *
 * @see https://github.com/nextcloud/server/issues/58646
 */
class FixDefaultAccountScopesToLocal implements IRepairStep {

	/**
	 * Properties whose default scope was changed from federated to local.
	 */
	private const AFFECTED_PROPERTIES = [
		IAccountManager::PROPERTY_DISPLAYNAME,
		IAccountManager::PROPERTY_EMAIL,
		IAccountManager::PROPERTY_AVATAR,
		IAccountManager::PROPERTY_PRONOUNS,
	];

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	public function getName(): string {
		return 'Fix default account property scopes from federated to local';
	}

	public function run(IOutput $output): void {
		$updated = 0;
		$processed = 0;

		$select = $this->connection->getQueryBuilder();
		$select->select('uid', 'data')
			->from('accounts');

		$update = $this->connection->getQueryBuilder();
		$update->update('accounts')
			->set('data', $update->createParameter('data'))
			->where($update->expr()->eq('uid', $update->createParameter('uid')));

		$result = $select->executeQuery();
		while ($row = $result->fetch()) {
			$processed++;
			$data = json_decode($row['data'], true);
			if (!is_array($data)) {
				continue;
			}

			$changed = false;
			foreach (self::AFFECTED_PROPERTIES as $property) {
				if (isset($data[$property]['scope'])
					&& $data[$property]['scope'] === IAccountManager::SCOPE_FEDERATED
				) {
					$data[$property]['scope'] = IAccountManager::SCOPE_LOCAL;
					$changed = true;
				}
			}

			if ($changed) {
				$update->setParameter('data', json_encode($data));
				$update->setParameter('uid', $row['uid']);
				$update->executeStatement();
				$updated++;
			}
		}
		$result->closeCursor();

		$output->info("Processed $processed accounts, updated $updated accounts with local scope defaults.");
	}
}
