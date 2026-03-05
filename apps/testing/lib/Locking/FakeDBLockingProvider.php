<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Testing\Locking;

use OC\Lock\DBLockingProvider;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Override;

class FakeDBLockingProvider extends DBLockingProvider {
	// Lock for 10 hours just to be sure
	public const TTL = 36000;

	/**
	 * Need a new child, because parent::connection is private instead of protected...
	 */
	protected IDBConnection $db;

	public function __construct(
		IDBConnection $connection,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($connection, $timeFactory);
		$this->db = $connection;
	}

	#[Override]
	public function releaseLock(string $path, int $type): void {
		// we DON'T keep shared locks till the end of the request
		if ($type === self::LOCK_SHARED) {
			$qb = $this->db->getQueryBuilder();
			$qb->update('file_locks')
				->set('lock', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT))
				->where($qb->expr()->eq('key', $qb->createNamedParameter($path, IQueryBuilder::PARAM_STR)))
				->andWhere($qb->expr()->eq('lock', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
				->executeStatement();
		}

		parent::releaseLock($path, $type);
	}

	public function __destruct() {
		// Prevent cleaning up at the end of the live time.
		// parent::__destruct();
	}
}
