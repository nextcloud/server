<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Migration;

use Closure;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Security\ICrypto;

class Version011901Date20240829164356 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $connection,
		private ICrypto $crypto,
	) {
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$qbUpdate = $this->connection->getQueryBuilder();
		$qbUpdate->update('oauth2_clients')
			->set('secret', $qbUpdate->createParameter('updateSecret'))
			->where(
				$qbUpdate->expr()->eq('id', $qbUpdate->createParameter('updateId'))
			);

		$qbSelect = $this->connection->getQueryBuilder();
		$qbSelect->select('id', 'secret')
			->from('oauth2_clients');
		$req = $qbSelect->executeQuery();
		while ($row = $req->fetch()) {
			$id = $row['id'];
			$storedEncryptedSecret = $row['secret'];
			$secret = $this->crypto->decrypt($storedEncryptedSecret);
			$hashedSecret = bin2hex($this->crypto->calculateHMAC($secret));
			$qbUpdate->setParameter('updateSecret', $hashedSecret, IQueryBuilder::PARAM_STR);
			$qbUpdate->setParameter('updateId', $id, IQueryBuilder::PARAM_INT);
			$qbUpdate->executeStatement();
		}
		$req->closeCursor();
	}
}
