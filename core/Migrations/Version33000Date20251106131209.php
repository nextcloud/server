<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\Attributes\DataCleansing;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Share\IShare;

#[DataCleansing(table: 'share', description: 'Fix share download permissions')]
class Version33000Date20251106131209 extends SimpleMigrationStep {
	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$qb = $this->connection->getQueryBuilder();
		$qb->update('share')
			->set('attributes', $qb->createNamedParameter('[["permissions","download",true]]'))
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_CIRCLE, IQueryBuilder::PARAM_INT)));

		if ($this->connection->getDatabaseProvider(true) === IDBConnection::PLATFORM_MYSQL) {
			$qb->andWhere($qb->expr()->eq('attributes', $qb->createFunction("JSON_ARRAY(JSON_ARRAY('permissions','download',null))"), IQueryBuilder::PARAM_JSON));
		} else {
			$qb->andWhere($qb->expr()->eq('attributes', $qb->createNamedParameter('[["permissions","download",null]]'), IQueryBuilder::PARAM_JSON));
		}

		$qb->executeStatement();
	}
}
