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
use OCP\IDBConnection;

class PreviewService {
	public function __construct(
		private readonly StorageFactory $storageFactory,
		private readonly PreviewMapper $previewMapper,
		private readonly IDBConnection $connection,
	) {
	}

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
		$maxQb = $this->connection->getQueryBuilder();
		$maxQb->select($maxQb->func()->max('id'))
			->from($this->previewMapper->getTableName())
			->groupBy('file_id');

		$qb = $this->connection->getQueryBuilder();
		$qb->select('file_id', 'storage_id')
			->from($this->previewMapper->getTableName())
			->where($qb->expr()->in('id', $qb->createFunction($maxQb->getSQL())));

		$result = $qb->executeQuery();

		$lastStorageId = -1;
		/** @var int[] $fileIds */
		$fileIds = [];

		// Previews next to each others in the database are likely in the same storage, so group them
		while ($row = $result->fetch()) {
			if ($lastStorageId !== $row['storage_id']) {
				if ($lastStorageId !== -1) {
					yield ['storageId' => $lastStorageId, 'fileIds' => $fileIds];
					$fileIds = [];
				}
				$lastStorageId = (int)$row['storage_id'];
			}
			$fileIds[] = (int)$row['file_id'];
		}

		if (count($fileIds) > 0) {
			yield ['storageId' => $lastStorageId, 'fileIds' => $fileIds];
		}
	}

	/**
	 * @return \Generator<Preview>
	 */
	public function getAvailablePreviewForFile(int $fileId): \Generator {
		return $this->previewMapper->getAvailablePreviewForFile($fileId);
	}

	/**
	 * @param int[] $mimeTypes
	 * @return \Generator<Preview>
	 */
	public function getPreviewsForMimeTypes(array $mimeTypes): \Generator {
		return $this->previewMapper->getPreviewsForMimeTypes($mimeTypes);
	}

	public function deleteAll(): void {
		$lastId = 0;
		while (true) {
			$previews = $this->previewMapper->getPreviews($lastId, 1000);
			$i = 0;
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
