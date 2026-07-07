<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<OcmTokenMap>
 */
class OcmTokenMapMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'ocm_token_map', OcmTokenMap::class);
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
	 * All mappings for a refresh token. Unlike findByRefreshToken this
	 * tolerates the duplicate rows a concurrent exchange can create.
	 *
	 * @return OcmTokenMap[]
	 */
	public function findAllByRefreshToken(string $refreshToken): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('refresh_token', $qb->createNamedParameter($refreshToken)));

		return $this->findEntities($qb);
	}

	/**
	 * All mappings whose access token has expired before $time.
	 *
	 * @return OcmTokenMap[]
	 */
	public function findExpired(int $time): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->lt('expires', $qb->createNamedParameter($time)));

		return $this->findEntities($qb);
	}
}
