<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<FederatedCalendarEntity>  */
class FederatedCalendarMapper extends QBMapper {
	public const TABLE_NAME = 'calendars_federated';

	public function __construct(
		IDBConnection $db,
		private readonly ITimeFactory $time,
	) {
		parent::__construct($db, self::TABLE_NAME, FederatedCalendarEntity::class);
	}

	/**
	 * @throws DoesNotExistException If there is no federated calendar with the given id.
	 */
	public function find(int $id): FederatedCalendarEntity {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->eq(
				'id',
				$qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
				IQueryBuilder::PARAM_INT,
			));
		return $this->findEntity($qb);
	}

	/**
	 * @return FederatedCalendarEntity[]
	 */
	public function findByPrincipalUri(string $principalUri): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->eq(
				'principaluri',
				$qb->createNamedParameter($principalUri, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			));
		return $this->findEntities($qb);
	}

	public function findByUri(string $principalUri, string $uri): ?FederatedCalendarEntity {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->eq(
				'principaluri',
				$qb->createNamedParameter($principalUri, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			))
			->andWhere($qb->expr()->eq(
				'uri',
				$qb->createNamedParameter($uri, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException $e) {
			return null;
		} catch (MultipleObjectsReturnedException $e) {
			// Should never happen
			return null;
		}
	}

	/**
	 * @return FederatedCalendarEntity[]
	 */
	public function findUnsyncedSinceBefore(int $beforeTimestamp): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->lt(
				'last_sync',
				$qb->createNamedParameter($beforeTimestamp, IQueryBuilder::PARAM_INT),
				IQueryBuilder::PARAM_INT,
			))
			// Omit unsynced calendars for now as they are synced by a separate job
			->andWhere($qb->expr()->isNotNull('last_sync'));
		return $this->findEntities($qb);
	}

	public function deleteById(int $id): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLE_NAME)
			->where($qb->expr()->eq(
				'id',
				$qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
				IQueryBuilder::PARAM_INT,
			));
		$qb->executeStatement();
	}

	public function updateSyncTime(int $id): void {
		$now = $this->time->getTime();

		$qb = $this->db->getQueryBuilder();
		$qb->update(self::TABLE_NAME)
			->set('last_sync', $qb->createNamedParameter($now, IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq(
				'id',
				$qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
				IQueryBuilder::PARAM_INT,
			));
		$qb->executeStatement();
	}

	public function updateSyncTokenAndTime(int $id, int $syncToken): void {
		$now = $this->time->getTime();

		$qb = $this->db->getQueryBuilder();
		$qb->update(self::TABLE_NAME)
			->set('sync_token', $qb->createNamedParameter($syncToken, IQueryBuilder::PARAM_INT))
			->set('last_sync', $qb->createNamedParameter($now, IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq(
				'id',
				$qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
				IQueryBuilder::PARAM_INT,
			));
		$qb->executeStatement();
	}

	/**
	 * @return \Generator<mixed, FederatedCalendarEntity>
	 */
	public function findAll(): \Generator {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME);

		$result = $qb->executeQuery();
		while ($row = $result->fetch()) {
			yield $this->mapRowToEntity($row);
		}
		$result->closeCursor();
	}

	public function countAll(): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->func()->count('*'))
			->from(self::TABLE_NAME);
		$result = $qb->executeQuery();
		$count = (int)$result->fetchOne();
		$result->closeCursor();
		return $count;
	}

	public function deleteByUri(string $principalUri, string $uri): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLE_NAME)
			->where($qb->expr()->eq(
				'principaluri',
				$qb->createNamedParameter($principalUri, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			))
			->andWhere($qb->expr()->eq(
				'uri',
				$qb->createNamedParameter($uri, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			));

		$qb->executeStatement();
	}

	/**
	 * @return FederatedCalendarEntity[]
	 */
	public function findByRemoteUrl(string $remoteUrl, string $principalUri, string $token): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->eq(
				'remote_url',
				$qb->createNamedParameter($remoteUrl, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			))
			->andWhere($qb->expr()->eq(
				'principaluri',
				$qb->createNamedParameter($principalUri, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			))
			->andWhere($qb->expr()->eq(
				'token',
				$qb->createNamedParameter($token, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			));

		return $this->findEntities($qb);
	}
}
