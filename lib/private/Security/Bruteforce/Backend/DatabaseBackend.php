<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Bruteforce\Backend;

use OCP\IDBConnection;

class DatabaseBackend implements IBackend {
	private const TABLE_NAME = 'bruteforce_attempts';

	public function __construct(
		private IDBConnection $db,
	) {
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAttempts(
		string $ipSubnet,
		int $maxAgeTimestamp,
		?string $action = null,
		?array $metadata = null,
	): int {
		$query = $this->db->getQueryBuilder();
		$query->select($query->func()->count('*', 'attempts'))
			->from(self::TABLE_NAME)
			->where($query->expr()->gt('occurred', $query->createNamedParameter($maxAgeTimestamp)))
			->andWhere($query->expr()->eq('subnet', $query->createNamedParameter($ipSubnet)));

		if ($action !== null) {
			$query->andWhere($query->expr()->eq('action', $query->createNamedParameter($action)));

			if ($metadata !== null) {
				$trimmedMetaData = $this->trimMetaData($metadata);
				$query->andWhere($query->expr()->eq('metadata', $query->createNamedParameter($trimmedMetaData)));
			}
		}

		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		return (int)$row['attempts'];
	}

	/**
	 * {@inheritDoc}
	 */
	public function resetAttempts(
		string $ipSubnet,
		?string $action = null,
		?array $metadata = null,
	): void {
		$query = $this->db->getQueryBuilder();
		$query->delete(self::TABLE_NAME)
			->where($query->expr()->eq('subnet', $query->createNamedParameter($ipSubnet)));

		if ($action !== null) {
			$query->andWhere($query->expr()->eq('action', $query->createNamedParameter($action)));

			if ($metadata !== null) {
				$trimmedMetaData = $this->trimMetaData($metadata);
				$query->andWhere($query->expr()->eq('metadata', $query->createNamedParameter($trimmedMetaData)));
			}
		}

		$query->executeStatement();
	}

	/**
	 * {@inheritDoc}
	 */
	public function registerAttempt(
		string $ip,
		string $ipSubnet,
		int $timestamp,
		string $action,
		array $metadata = [],
	): void {
		$values = [
			'ip' => $ip,
			'subnet' => $ipSubnet,
			'action' => $action,
			'metadata' => $metadata,
			'occurred' => $timestamp,
		];

		$qb = $this->db->getQueryBuilder();
		$qb->insert(self::TABLE_NAME);
		foreach ($values as $column => $value) {
			if ($column === 'metadata') {
				$value = $this->trimMetaData($value);
			}
			$qb->setValue($column, $qb->createNamedParameter($value));
		}
		$qb->executeStatement();
	}

	protected function trimMetaData(array $metadata): string {
		try {
			$data = json_encode($metadata, JSON_THROW_ON_ERROR);
		} catch (\JsonException) {
			$data = 'INVALID';
		}

		$trimmed = substr($data, 0, 254);
		if ($trimmed !== $data) {
			$trimmed .= '…';
		}
		return $trimmed;
	}
}
