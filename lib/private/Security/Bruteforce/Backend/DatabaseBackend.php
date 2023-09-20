<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
				$query->andWhere($query->expr()->eq('metadata', $query->createNamedParameter(json_encode($metadata))));
			}
		}

		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		return (int) $row['attempts'];
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
				$query->andWhere($query->expr()->eq('metadata', $query->createNamedParameter(json_encode($metadata))));
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
			'metadata' => json_encode($metadata),
			'occurred' => $timestamp,
		];

		$qb = $this->db->getQueryBuilder();
		$qb->insert(self::TABLE_NAME);
		foreach ($values as $column => $value) {
			$qb->setValue($column, $qb->createNamedParameter($value));
		}
		$qb->executeStatement();
	}
}
