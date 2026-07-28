<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

use OC\Files\SimpleFS\SimpleFile;
use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\Storage\StorageFactory;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class PreviewMigrationService {
	private IAppData $appData;
	private string $previewRootPath;

	public function __construct(
		private readonly IConfig $config,
		private readonly IRootFolder $rootFolder,
		private readonly LoggerInterface $logger,
		private readonly IMimeTypeDetector $mimeTypeDetector,
		private readonly IMimeTypeLoader $mimeTypeLoader,
		private readonly IDBConnection $connection,
		private readonly PreviewMapper $previewMapper,
		private readonly StorageFactory $storageFactory,
		IAppDataFactory $appDataFactory,
	) {
		$this->appData = $appDataFactory->get('preview');
		$this->previewRootPath = 'appdata_' . $this->config->getSystemValueString('instanceid') . '/preview/';
	}

	/**
	 * @param array<string|int, string[]> $previewFolders
	 * @return Preview[]
	 */
	public function migrateFileId(int $fileId, bool $flatPath): array {
		$previews = [];
		$internalPath = $this->getInternalFolder((string)$fileId, $flatPath);
		try {
			$folder = $this->appData->getFolder($internalPath);
		} catch (NotFoundException) {
			return [];
		}

		/**
		 * @var list<array{file: SimpleFile, preview: Preview}> $previewFiles
		 */
		$previewFiles = [];

		foreach ($folder->getDirectoryListing() as $previewFile) {
			$path = $fileId . '/' . $previewFile->getName();
			/** @var SimpleFile $previewFile */
			$preview = Preview::fromPath($path, $this->mimeTypeDetector);
			if ($preview === false) {
				$this->logger->error('Unable to import old preview at path.');
				continue;
			}
			$preview->generateId();
			$preview->setSize($previewFile->getSize());
			$preview->setMtime($previewFile->getMtime());
			$preview->setOldFileId($previewFile->getId());
			$preview->setEncrypted(false);

			$previewFiles[] = [
				'file' => $previewFile,
				'preview' => $preview,
			];
		}

		if (empty($previewFiles)) {
			return $previews;
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->select('storage', 'etag', 'mimetype')
			->from('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileId)))
			->setMaxResults(1);

		$result = $qb->executeQuery();
		$result = $result->fetchAssociative();

		if ($result !== false) {
			$oldFileIdsToDelete = [];
			try {
				foreach ($previewFiles as $previewFile) {
					/** @var Preview $preview */
					$preview = $previewFile['preview'];
					/** @var SimpleFile $file */
					$file = $previewFile['file'];
					$preview->setStorageId($result['storage']);
					$preview->setEtag($result['etag']);
					$preview->setSourceMimeType($this->mimeTypeLoader->getMimetypeById((int)$result['mimetype']));
					$preview->generateId();
					try {
						$preview = $this->previewMapper->insert($preview);
					} catch (Exception $e) {
						if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
							throw $e;
						}

						// We already have this preview in the preview table, skip
						$oldFileIdsToDelete[] = $file->getId();
						continue;
					}

					try {
						$this->storageFactory->migratePreview($preview, $file);
						// Do not call $file->delete() as this will also delete the file from the file system
					} catch (\Exception $e) {
						$this->previewMapper->delete($preview);
						throw $e;
					}

					$oldFileIdsToDelete[] = $file->getId();
					$previews[] = $preview;
				}
			} finally {
				$this->deleteOldFileCacheEntries($oldFileIdsToDelete);
			}
		} else {
			// No matching fileId, delete preview
			try {
				$this->connection->beginTransaction();
				foreach ($previewFiles as $previewFile) {
					/** @var SimpleFile $file */
					$file = $previewFile['file'];
					$file->delete();
				}
				$this->connection->commit();
			} catch (Exception) {
				$this->connection->rollback();
			}
		}

		$this->deleteFolder($internalPath);

		return $previews;
	}

	private static function getInternalFolder(string $name, bool $flatPath): string {
		if ($flatPath) {
			return $name;
		}
		return implode('/', str_split(substr(md5($name), 0, 7))) . '/' . $name;
	}

	/**
	 * @param list<int> $fileIds
	 */
	private function deleteOldFileCacheEntries(array $fileIds): void {
		if ($fileIds === []) {
			return;
		}

		foreach (array_chunk($fileIds, 1000) as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb->delete('filecache')
				->where($qb->expr()->in('fileid', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)))
				->hintShardKey('storage', $this->rootFolder->getMountPoint()->getNumericStorageId())
				->executeStatement();
		}
	}

	private function deleteFolder(string $path): void {
		$current = $path;

		$rootFolderId = $this->rootFolder->getMountPoint()->getNumericStorageId();
		while (true) {
			$appDataPath = $this->previewRootPath . $current;
			$qb = $this->connection->getQueryBuilder();
			$qb->delete('filecache')
				->where($qb->expr()->eq('path_hash', $qb->createNamedParameter(md5($appDataPath))))
				->andWhere($qb->expr()->eq(
					'storage',
					$qb->createNamedParameter($rootFolderId),
				))
				->executeStatement();

			$current = dirname($current);
			if ($current === '/' || $current === '.' || $current === '') {
				break;
			}

			if ($this->folderHasChildren($rootFolderId, $this->previewRootPath . $current)) {
				break;
			}
		}
	}

	private function folderHasChildren(int $storageId, string $path): bool {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('fileid')
			->from('filecache')
			->where($qb->expr()->eq('path_hash', $qb->createNamedParameter(md5($path))))
			->andWhere($qb->expr()->eq('storage', $qb->createNamedParameter($storageId)))
			->setMaxResults(1);
		$cursor = $qb->executeQuery();
		$folderId = $cursor->fetchOne();
		$cursor->closeCursor();

		if ($folderId === false) {
			// The folder itself is already gone, nothing to check.
			return false;
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->select('fileid')
			->from('filecache')
			->where($qb->expr()->eq('parent', $qb->createNamedParameter((int)$folderId)))
			->setMaxResults(1);
		$cursor = $qb->executeQuery();
		$hasChild = $cursor->fetchOne() !== false;
		$cursor->closeCursor();

		return $hasChild;
	}
}
