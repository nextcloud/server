<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OC\Preview\Storage\Root;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;

class BackgroundCleanupJob extends TimedJob {

	public function __construct(
		ITimeFactory $timeFactory,
		private IDBConnection $connection,
		private Root $previewFolder,
		private IMimeTypeLoader $mimeTypeLoader,
		private bool $isCLI,
	) {
		parent::__construct($timeFactory);
		// Run at most once an hour
		$this->setInterval(60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	public function run($argument) {
		foreach ($this->getDeletedFiles() as $fileId) {
			try {
				$preview = $this->previewFolder->getFolder((string)$fileId);
				$preview->delete();
			} catch (NotFoundException $e) {
				// continue
			} catch (NotPermittedException $e) {
				// continue
			}
		}
	}

	private function getDeletedFiles(): \Iterator {
		yield from $this->getOldPreviewLocations();
		yield from $this->getNewPreviewLocations();
	}

	private function getOldPreviewLocations(): \Iterator {
		if ($this->connection->getShardDefinition('filecache')) {
			// sharding is new enough that we don't need to support this
			return;
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->select('a.name')
			->from('filecache', 'a')
			->leftJoin('a', 'filecache', 'b', $qb->expr()->eq(
				$qb->expr()->castColumn('a.name', IQueryBuilder::PARAM_INT), 'b.fileid'
			))
			->where(
				$qb->expr()->andX(
					$qb->expr()->isNull('b.fileid'),
					$qb->expr()->eq('a.storage', $qb->createNamedParameter($this->previewFolder->getStorageId())),
					$qb->expr()->eq('a.parent', $qb->createNamedParameter($this->previewFolder->getId())),
					$qb->expr()->like('a.name', $qb->createNamedParameter('__%')),
					$qb->expr()->eq('a.mimetype', $qb->createNamedParameter($this->mimeTypeLoader->getId('httpd/unix-directory')))
				)
			);

		if (!$this->isCLI) {
			$qb->setMaxResults(10);
		}

		$cursor = $qb->executeQuery();

		while ($row = $cursor->fetch()) {
			yield $row['name'];
		}

		$cursor->closeCursor();
	}

	private function getNewPreviewLocations(): \Iterator {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('path', 'mimetype')
			->from('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($this->previewFolder->getId())));
		$cursor = $qb->executeQuery();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === null) {
			return [];
		}

		if ($this->connection->getShardDefinition('filecache')) {
			$chunks = $this->getAllPreviewIds($data['path'], 1000);
			foreach ($chunks as $chunk) {
				yield from $this->findMissingSources($chunk);
			}

			return;
		}

		/*
		 * This lovely like is the result of the way the new previews are stored
		 * We take the md5 of the name (fileid) and split the first 7 chars. That way
		 * there are not a gazillion files in the root of the preview appdata.
		 */
		$like = $this->connection->escapeLikeParameter($data['path']) . '/_/_/_/_/_/_/_/%';

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
		$qb->select('a.name')
			->from('filecache', 'a')
			->leftJoin('a', 'filecache', 'b', $qb->expr()->eq(
				$qb->expr()->castColumn('a.name', IQueryBuilder::PARAM_INT), 'b.fileid'
			))
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('a.storage', $qb->createNamedParameter($this->previewFolder->getStorageId())),
					$qb->expr()->isNull('b.fileid'),
					$qb->expr()->like('a.path', $qb->createNamedParameter($like)),
					$qb->expr()->eq('a.mimetype', $qb->createNamedParameter($this->mimeTypeLoader->getId('httpd/unix-directory')))
				)
			);

		if (!$this->isCLI) {
			$qb->setMaxResults(10);
		}

		$cursor = $qb->executeQuery();

		while ($row = $cursor->fetch()) {
			yield $row['name'];
		}

		$cursor->closeCursor();
	}

	private function getAllPreviewIds(string $previewRoot, int $chunkSize): \Iterator {
		// See `getNewPreviewLocations` for some more info about the logic here
		$like = $this->connection->escapeLikeParameter($previewRoot) . '/_/_/_/_/_/_/_/%';

		$qb = $this->connection->getQueryBuilder();
		$qb->select('name', 'fileid')
			->from('filecache')
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('storage', $qb->createNamedParameter($this->previewFolder->getStorageId())),
					$qb->expr()->like('path', $qb->createNamedParameter($like)),
					$qb->expr()->eq('mimetype', $qb->createNamedParameter($this->mimeTypeLoader->getId('httpd/unix-directory'))),
					$qb->expr()->gt('fileid', $qb->createParameter('min_id')),
				)
			)
			->orderBy('fileid', 'ASC')
			->setMaxResults($chunkSize);

		$minId = 0;
		while (true) {
			$qb->setParameter('min_id', $minId);
			$rows = $qb->executeQuery()->fetchAll();
			if (count($rows) > 0) {
				$minId = $rows[count($rows) - 1]['fileid'];
				yield array_map(function ($row) {
					return (int)$row['name'];
				}, $rows);
			} else {
				break;
			}
		}
	}

	private function findMissingSources(array $ids): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('fileid')
			->from('filecache')
			->where($qb->expr()->in('fileid', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)));
		$found = $qb->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
		return array_diff($ids, $found);
	}
}
