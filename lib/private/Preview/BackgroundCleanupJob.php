<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OC\Preview\Db\Preview;
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
		private readonly IDBConnection $connection,
		private readonly PreviewService $previewService,
		private readonly bool $isCLI,
	) {
		parent::__construct($timeFactory);
		// Run at most once an hour
		$this->setInterval(60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	public function run($argument): void {
		foreach ($this->getDeletedFiles() as $fileId) {
			$previewIds = [];
			foreach ($this->previewService->getAvailablePreviewsForFile($fileId) as $preview) {
				$this->previewService->deletePreview($preview);
			}
		}
	}

	/**
	 * @return \Iterator<FileId>
	 */
	private function getDeletedFiles(): \Iterator {
		if ($this->connection->getShardDefinition('filecache')) {
			foreach ($this->previewService->getAvailableFileIds() as $availableFileIdGroup) {
				$fileIds = $this->findMissingSources($availableFileIdGroup['storageId'], $availableFileIdGroup['fileIds']);
				foreach ($fileIds as $fileId) {
					yield $fileId;
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
		$qb->select('p.file_id')
			->from('previews', 'p')
			->leftJoin('p', 'filecache', 'f', $qb->expr()->eq(
				'p.file_id', 'f.fileid'
			))
			->where($qb->expr()->isNull('f.fileid'));

		if (!$this->isCLI) {
			$qb->setMaxResults(10);
		}

		$cursor = $qb->executeQuery();
		while ($row = $cursor->fetch()) {
			yield (int)$row['file_id'];
		}
		$cursor->closeCursor();
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
