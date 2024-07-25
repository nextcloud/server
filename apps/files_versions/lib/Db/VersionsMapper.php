<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Versions\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @extends QBMapper<VersionEntity>
 */
class VersionsMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'files_versions', VersionEntity::class);
	}

	/**
	 * @return VersionEntity[]
	 */
	public function findAllVersionsForFileId(int $fileId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			 ->from($this->getTableName())
			 ->where($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId)));

		return $this->findEntities($qb);
	}

	/**
	 * @return VersionEntity
	 */
	public function findCurrentVersionForFileId(int $fileId): VersionEntity {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			 ->from($this->getTableName())
			 ->where($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId)))
			 ->orderBy('timestamp', 'DESC')
			 ->setMaxResults(1);

		return $this->findEntity($qb);
	}

	public function findVersionForFileId(int $fileId, int $timestamp): VersionEntity {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			 ->from($this->getTableName())
			 ->where($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId)))
			 ->andWhere($qb->expr()->eq('timestamp', $qb->createNamedParameter($timestamp)));

		return $this->findEntity($qb);
	}

	public function deleteAllVersionsForFileId(int $fileId): int {
		$qb = $this->db->getQueryBuilder();

		return $qb->delete($this->getTableName())
			 ->where($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId)))
			 ->executeStatement();
	}

	public function deleteAllVersionsForUser(int $storageId, ?string $path = null): void {
		$fileIdsGenerator = $this->getFileIdsGenerator($storageId, $path);

		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->in('file_id', $qb->createParameter('file_ids')));

		foreach ($fileIdsGenerator as $fileIds) {
			$qb->setParameter('file_ids', $fileIds, IQueryBuilder::PARAM_INT_ARRAY);
			$qb->executeStatement();
		}
	}

	private function getFileIdsGenerator(int $storageId, ?string $path): \Generator {
		$offset = 0;
		do {
			$qb = $this->db->getQueryBuilder();
			$qb->select('fileid')
				->from('filecache')
				->where($qb->expr()->eq('storage', $qb->createNamedParameter($storageId, IQueryBuilder::PARAM_STR)))
				->andWhere($qb->expr()->like('path', $qb->createNamedParameter('files' . ($path ? '/' . $this->db->escapeLikeParameter($path) : '') . '/%', IQueryBuilder::PARAM_STR)))
				->andWhere($qb->expr()->gt('fileid', $qb->createParameter('offset')))
				->setMaxResults(1000)
				->orderBy('fileid', 'ASC');

			$qb->setParameter('offset', $offset, IQueryBuilder::PARAM_INT);
			$result = $qb->executeQuery();
			$fileIds = $result->fetchAll(\PDO::FETCH_COLUMN);
			$offset = end($fileIds);

			yield $fileIds;
		} while (!empty($fileIds));
	}
}
