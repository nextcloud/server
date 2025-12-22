<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\TwoFactorAuth\Db;

use OCP\IDBConnection;
use function array_map;

/**
 * Data access object to query and assign (provider_id, uid, enabled) tuples of
 * 2FA providers
 */
class ProviderUserAssignmentDao {
	public const TABLE_NAME = 'twofactor_providers';

	public function __construct(
		private IDBConnection $conn,
	) {
	}

	/**
	 * Get all assigned provider IDs for the given user ID
	 *
	 * @return array<string, bool> where the array key is the provider ID (string) and the
	 *                             value is the enabled state (bool)
	 */
	public function getState(string $uid): array {
		$qb = $this->conn->getQueryBuilder();

		$query = $qb->select('provider_id', 'enabled')
			->from(self::TABLE_NAME)
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)));
		$result = $query->executeQuery();
		$providers = [];
		foreach ($result->fetchAll() as $row) {
			$providers[(string)$row['provider_id']] = (int)$row['enabled'] === 1;
		}
		$result->closeCursor();

		return $providers;
	}

	/**
	 * Persist a new/updated (provider_id, uid, enabled) tuple
	 */
	public function persist(string $providerId, string $uid, int $enabled): void {
		$conn = $this->conn;

		// Insert a new entry
		if ($conn->insertIgnoreConflict(self::TABLE_NAME, [
			'provider_id' => $providerId,
			'uid' => $uid,
			'enabled' => $enabled,
		])) {
			return;
		}

		// There is already an entry -> update it
		$qb = $conn->getQueryBuilder();
		$updateQuery = $qb->update(self::TABLE_NAME)
			->set('enabled', $qb->createNamedParameter($enabled))
			->where($qb->expr()->eq('provider_id', $qb->createNamedParameter($providerId)))
			->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($uid)));
		$updateQuery->executeStatement();
	}

	/**
	 * Delete all provider states of a user and return the provider IDs
	 *
	 * @return list<array{provider_id: string, uid: string, enabled: bool}>
	 */
	public function deleteByUser(string $uid): array {
		$qb1 = $this->conn->getQueryBuilder();
		$selectQuery = $qb1->select('*')
			->from(self::TABLE_NAME)
			->where($qb1->expr()->eq('uid', $qb1->createNamedParameter($uid)));
		$selectResult = $selectQuery->executeQuery();
		$rows = $selectResult->fetchAll();
		$selectResult->closeCursor();

		$qb2 = $this->conn->getQueryBuilder();
		$deleteQuery = $qb2
			->delete(self::TABLE_NAME)
			->where($qb2->expr()->eq('uid', $qb2->createNamedParameter($uid)));
		$deleteQuery->executeStatement();

		return array_values(array_map(function (array $row) {
			return [
				'provider_id' => (string)$row['provider_id'],
				'uid' => (string)$row['uid'],
				'enabled' => ((int)$row['enabled']) === 1,
			];
		}, $rows));
	}

	public function deleteAll(string $providerId): void {
		$qb = $this->conn->getQueryBuilder();

		$deleteQuery = $qb->delete(self::TABLE_NAME)
			->where($qb->expr()->eq('provider_id', $qb->createNamedParameter($providerId)));

		$deleteQuery->executeStatement();
	}
}
