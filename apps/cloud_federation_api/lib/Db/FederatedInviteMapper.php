<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<FederatedInvite>
 */
class FederatedInviteMapper extends QBMapper {
	public const TABLE_NAME = 'federated_invites';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE_NAME);
	}

	public function findByToken(string $token): FederatedInvite {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('federated_invites')
			->where($qb->expr()->eq('token', $qb->createNamedParameter($token)));
		return $this->findEntity($qb);
	}

}
