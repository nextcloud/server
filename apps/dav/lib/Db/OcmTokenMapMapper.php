<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<OcmTokenMap>
 */
class OcmTokenMapMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'dav_ocm_token_map', OcmTokenMap::class);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function getByAccessTokenId(int $accessTokenId): OcmTokenMap {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('access_token_id', $qb->createNamedParameter($accessTokenId)));

		return $this->findEntity($qb);
	}

	/**
	 * Find the current mapping for a given refresh token, if any.
	 */
	public function findByRefreshToken(string $refreshToken): ?OcmTokenMap {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('refresh_token', $qb->createNamedParameter($refreshToken)));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			return null;
		}
	}

	public function deleteExpired(int $time): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->lt('expires', $qb->createNamedParameter($time)));
		$qb->executeStatement();
	}
}
