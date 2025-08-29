<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OC\Preview\Db\PreviewMapper;
use OC\Preview\Storage\StorageFactory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @psalm-type FileId int
 * @psalm-type StorageId int
 */
class BackgroundCleanupJob extends TimedJob {

	public function __construct(
		ITimeFactory $timeFactory,
		readonly private IDBConnection $connection,
		readonly private PreviewMapper $previewMapper,
		readonly private StorageFactory $storageFactory,
		readonly private bool $isCLI,
	) {
		parent::__construct($timeFactory);
		// Run at most once an hour
		$this->setInterval(60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	public function run($argument): void {
		foreach ($this->getDeletedFiles() as $chunk) {
			foreach ($chunk as $storage => $fileIds) {
				foreach ($this->previewMapper->getByFileIds($storage, $fileIds) as $previews) {
					$previewIds = [];
					foreach ($previews as $preview) {
						$previewIds[] = $preview->getId();
						$this->storageFactory->deletePreview($preview);
					}

					$this->previewMapper->deleteByIds($storage, $previewIds);
				};
			}
		}
	}

	/**
	 * @return \Iterator<array<StorageId, FileId[]>>
	 */
	private function getDeletedFiles(): \Iterator {
		if ($this->connection->getShardDefinition('filecache')) {
			$chunks = $this->getAllPreviewIds(1000);
			foreach ($chunks as $chunk) {
				foreach ($chunk as $storage => $preview) {
					yield [$storage => $this->findMissingSources($storage, $preview)];
				}
			}

			return;
		}

		/*
		 * Deleting a file will not delete related previews right away.
		 *
		 * A delete request is usually an HTTP request.
		 * The preview deleting is done by a background job to avoid timeouts.
		 *
		 * Previews for a file are stored within a folder in appdata_/preview using the fileid as folder name.
		 * Preview folders in oc_filecache are identified by a.storage, a.path (cf. $like) and a.mimetype.
		 *
		 * To find preview folders to delete, we query oc_filecache for a preview folder in app data, matching the preview folder structure
		 * and use the name to left join oc_filecache on a.name = b.fileid. A left join returns all rows from the left table (a),
		 * even if there are no matches in the right table (b).
		 *
		 * If the related file is deleted, b.fileid will be null and the preview folder can be deleted.
		 */
		$qb = $this->connection->getQueryBuilder();
		$qb->select('p.storage_id', 'p.file_id')
			->from('previews', 'p')
			->leftJoin('p', 'filecache', 'f', $qb->expr()->eq(
				'p.file_id', 'f.fileid'
			))
			->where($qb->expr()->isNull('f.fileid'));

		if (!$this->isCLI) {
			$qb->setMaxResults(10);
		}

		$cursor = $qb->executeQuery();

		$lastStorageId = null;
		/** @var FileId[] $tmpResult */
		$tmpResult = [];
		while ($row = $cursor->fetch()) {
			if ($lastStorageId === null) {
				$lastStorageId = $row['storage_id'];
			} else if ($lastStorageId !== $row['storage_id']) {
				yield [$lastStorageId => $tmpResult];
				$tmpResult = [];
				$lastStorageId = $row['storage_id'];
			}
			$tmpResult[] = $row['file_id'];
		}

		if (!empty($tmpResult)) {
			yield [$lastStorageId => $tmpResult];
		}

		$cursor->closeCursor();
	}

	/**
	 * @return \Iterator<array<StorageId, FileId[]>>
	 */
	private function getAllPreviewIds(int $chunkSize): \Iterator {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('id', 'file_id', 'storage_id')
			->from('previews')
			->where(
				$qb->expr()->gt('id', $qb->createParameter('min_id')),
			)
			->orderBy('id', 'ASC')
			->setMaxResults($chunkSize);

		$minId = 0;
		while (true) {
			$qb->setParameter('min_id', $minId);
			$rows = $qb->executeQuery()->fetchAll();
			if (count($rows) > 0) {
				$minId = $rows[count($rows) - 1]['id'];
				$result = [];
				foreach ($rows as $row) {
					if (!isset($result[$row['storage_id']])) {
						$result[$row['storage_id']] = [];
					}
					$result[$row['storage_id']][] = $row['file_id'];
				}
				yield $result;
			} else {
				break;
			}
		}
	}

	/**
	 * @param FileId[] $ids
	 * @return FileId[]
	 */
	private function findMissingSources(int $storage, array $ids): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('fileid')
			->from('filecache')
			->where($qb->expr()->andX(
				$qb->expr()->in('fileid', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)),
				$qb->expr()->eq('storage', $qb->createNamedParameter($storage, IQueryBuilder::PARAM_INT)),
			));
		$found = $qb->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
		return array_diff($ids, $found);
	}
}
