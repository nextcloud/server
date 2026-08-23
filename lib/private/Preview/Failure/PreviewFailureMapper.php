<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Failure;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<PreviewFailure>
 */
class PreviewFailureMapper extends QBMapper {
	public const TABLE = 'preview_failures';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE, PreviewFailure::class);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function find(int $id): PreviewFailure {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function findByFileId(int $fileId): PreviewFailure {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE)
			->where($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/**
	 * @return list<PreviewFailure>
	 */
	public function search(?string $mime = null, ?string $provider = null, ?int $since = null, int $limit = 100, int $offset = 0): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE)
			->orderBy('last_attempt', 'DESC')
			->setMaxResults($limit)
			->setFirstResult($offset);

		if ($mime !== null && $mime !== '') {
			$qb->andWhere($qb->expr()->eq('mime', $qb->createNamedParameter($mime)));
		}
		if ($provider !== null && $provider !== '') {
			$qb->andWhere($qb->expr()->eq('provider', $qb->createNamedParameter($provider)));
		}
		if ($since !== null) {
			$qb->andWhere($qb->expr()->gte('last_attempt', $qb->createNamedParameter($since, IQueryBuilder::PARAM_INT)));
		}

		return $this->findEntities($qb);
	}

	public function deleteByFileId(int $fileId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLE)
			->where($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}

	public function deleteAll(): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLE)->executeStatement();
	}

	public function deleteOlderThan(int $timestamp): int {
		$qb = $this->db->getQueryBuilder();
		return $qb->delete(self::TABLE)
			->where($qb->expr()->lt('last_attempt', $qb->createNamedParameter($timestamp, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}

	/**
	 * Drop oldest rows until the table is at most $maxRows long.
	 */
	public function pruneToMaxRows(int $maxRows): void {
		if ($maxRows <= 0) {
			return;
		}
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->func()->count('*'))
			->from(self::TABLE);
		$count = (int)$qb->executeQuery()->fetchOne();
		$excess = $count - $maxRows;
		if ($excess <= 0) {
			return;
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select('id')
			->from(self::TABLE)
			->orderBy('last_attempt', 'ASC')
			->setMaxResults($excess);
		$ids = $qb->executeQuery()->fetchFirstColumn();
		if ($ids === []) {
			return;
		}
		$delete = $this->db->getQueryBuilder();
		$delete->delete(self::TABLE)
			->where($delete->expr()->in('id', $delete->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)))
			->executeStatement();
	}
}
