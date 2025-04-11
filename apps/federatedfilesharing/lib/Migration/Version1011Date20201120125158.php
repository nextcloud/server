<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FederatedFileSharing\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1011Date20201120125158 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('federated_reshares')) {
			$table = $schema->getTable('federated_reshares');
			$remoteIdColumn = $table->getColumn('remote_id');
			if ($remoteIdColumn && $remoteIdColumn->getType()->getName() !== Types::STRING) {
				$remoteIdColumn->setNotnull(false);
				$remoteIdColumn->setType(Type::getType(Types::STRING));
				$remoteIdColumn->setOptions(['length' => 255]);
				$remoteIdColumn->setDefault('');
				return $schema;
			}
		}

		return null;
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$qb = $this->connection->getQueryBuilder();
		$qb->update('federated_reshares')
			->set('remote_id', $qb->createNamedParameter(''))
			->where($qb->expr()->eq('remote_id', $qb->createNamedParameter('-1')));
		$qb->execute();
	}
}
