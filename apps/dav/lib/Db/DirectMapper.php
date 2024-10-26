<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Direct>
 */
class DirectMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'directlink', Direct::class);
	}

	/**
	 * @param string $token
	 * @return Direct
	 * @throws DoesNotExistException
	 */
	public function getByToken(string $token): Direct {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('token', $qb->createNamedParameter($token))
			);

		return parent::findEntity($qb);
	}

	public function deleteExpired(int $expiration) {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where(
				$qb->expr()->lt('expiration', $qb->createNamedParameter($expiration))
			);

		$qb->executeStatement();
	}
}
