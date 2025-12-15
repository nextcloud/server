<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\Storage\StorageFactory;
use OCP\DB\Exception;
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;

class PreviewService {
	public function __construct(
		private readonly StorageFactory $storageFactory,
		private readonly PreviewMapper $previewMapper,
		private readonly IDBConnection $connection,
	) {
	}

	/**
	 * @throws NotPermittedException
	 * @throws Exception
	 */
	public function deletePreview(Preview $preview): void {
		$this->storageFactory->deletePreview($preview);
		$this->previewMapper->delete($preview);
	}

	/**
	 * Get storageId and fileIds for which we have at least one preview.
	 *
	 * @return \Generator<array{storageId: int, fileIds: int[]}>
	 */
	public function getAvailableFileIds(): \Generator {
		$lastId = null;
		while (true) {
			$maxQb = $this->connection->getQueryBuilder();
			$maxQb->selectAlias($maxQb->func()->max('id'), 'max_id')
				->from($this->previewMapper->getTableName())
				->groupBy('file_id')
				->orderBy('max_id', 'ASC');

			$qb = $this->connection->getQueryBuilder();

			if ($lastId !== null) {
				$maxQb->andWhere($maxQb->expr()->gt('id', $qb->createNamedParameter($lastId)));
			}

			$qb->select('id', 'file_id', 'storage_id')
				->from($this->previewMapper->getTableName(), 'p1')
				->innerJoin('p1', $qb->createFunction('(' . $maxQb->getSQL() . ')'), 'p2', $qb->expr()->eq('p1.id', 'p2.max_id'))
				->setMaxResults(1000);

			$result = $qb->executeQuery();

			$lastStorageId = -1;
			/** @var int[] $fileIds */
			$fileIds = [];

			$found = false;
			// Previews next to each others in the database are likely in the same storage, so group them
			while ($row = $result->fetch()) {
				$found = true;
				if ($lastStorageId !== (int)$row['storage_id']) {
					if ($lastStorageId !== -1) {
						yield ['storageId' => $lastStorageId, 'fileIds' => $fileIds];
						$fileIds = [];
					}
					$lastStorageId = (int)$row['storage_id'];
				}
				$fileIds[] = (int)$row['file_id'];
				$lastId = $row['id'];
			}

			if (count($fileIds) > 0) {
				yield ['storageId' => $lastStorageId, 'fileIds' => $fileIds];
			}

			if (!$found) {
				break;
			}
		}
	}

	/**
	 * @return \Generator<Preview>
	 */
	public function getAvailablePreviewsForFile(int $fileId): \Generator {
		return $this->previewMapper->getAvailablePreviewsForFile($fileId);
	}

	/**
	 * @param string[] $mimeTypes
	 * @return \Generator<Preview>
	 */
	public function getPreviewsForMimeTypes(array $mimeTypes): \Generator {
		return $this->previewMapper->getPreviewsForMimeTypes($mimeTypes);
	}

	/**
	 * @throws NotPermittedException
	 * @throws Exception
	 */
	public function deleteAll(): void {
		$lastId = 0;
		while (true) {
			$previews = $this->previewMapper->getPreviews($lastId, 1000);
			$i = 0;

			// FIXME: Should we use transaction here? Du to the I/O created when
			// deleting the previews from the storage, which might be on a network
			// This might take a non trivial amount of time where the DB is locked.
			foreach ($previews as $preview) {
				$this->deletePreview($preview);
				$i++;
				$lastId = $preview->getId();
			}

			if ($i !== 1000) {
				break;
			}
		}
	}

	/**
	 * @param int[] $fileIds
	 * @return array<int, Preview[]>
	 */
	public function getAvailablePreviews(array $fileIds): array {
		return $this->previewMapper->getAvailablePreviews($fileIds);
	}
}
