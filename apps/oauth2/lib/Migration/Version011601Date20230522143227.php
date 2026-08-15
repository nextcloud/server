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

final class Version011601Date20230522143227 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $connection,
		private ICrypto $crypto,
	) {
	}

	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		$schema = $schemaClosure();

		if ($schema->hasTable('oauth2_clients')) {
			$table = $schema->getTable('oauth2_clients');
			if ($table->hasColumn('secret')) {
				$column = $table->getColumn('secret');
				$column->setLength(512);
				return $schema;
			}
		}

		return null;
	}

	#[\Override]
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
		while ($row = $req->fetchAssociative()) {
			$id = (int)$row['id'];
			$secret = (string)$row['secret'];
			$encryptedSecret = $this->crypto->encrypt($secret);
			$qbUpdate->setParameter('updateSecret', $encryptedSecret, IQueryBuilder::PARAM_STR);
			$qbUpdate->setParameter('updateId', $id, IQueryBuilder::PARAM_INT);
			$qbUpdate->executeStatement();
		}
		$req->closeCursor();
	}
}
