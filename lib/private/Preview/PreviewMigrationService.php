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
use OCP\Files\Cache\ICacheEntry;
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
	 * @param list<ICacheEntry|SimpleFile>|null $entries Preview file entries already fetched by the caller.
	 * @return Preview[]
	 */
	public function migrateFileId(int $fileId, bool $flatPath, ?array $entries = null): array {
		$previews = [];
		$internalPath = $this->getInternalFolder((string)$fileId, $flatPath);

		if ($entries === null) {
			try {
				$entries = $this->appData->getFolder($internalPath)->getDirectoryListing();
			} catch (NotFoundException) {
				return [];
			}
		}

		/**
		 * @var list<Preview> $previewsToInsert
		 */
		$previewsToInsert = [];

		foreach ($entries as $entry) {
			$path = $fileId . '/' . $entry->getName();
			$preview = Preview::fromPath($path, $this->mimeTypeDetector);
			if ($preview === false) {
				$this->logger->error('Unable to import old preview at path.');
				continue;
			}
			$preview->generateId();
			$preview->setSize($entry->getSize());
			$preview->setMtime($entry->getMTime());
			$preview->setOldFileId($entry->getId());
			$preview->setEncrypted(false);

			$previewsToInsert[] = $preview;
		}

		if (empty($previewsToInsert)) {
			$this->deleteFolder($internalPath);

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
				foreach ($previewsToInsert as $preview) {
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
						$oldFileIdsToDelete[] = $preview->getOldFileId();
						continue;
					}

					try {
						$this->storageFactory->migratePreview($preview);
						// Do not delete the old file via a Node here, as that would also
						// delete it from the file system; only its filecache row is stale.
					} catch (\Exception $e) {
						$this->previewMapper->delete($preview);
						throw $e;
					}

					$oldFileIdsToDelete[] = $preview->getOldFileId();
					$previews[] = $preview;
				}
			} finally {
				$this->deleteOldFileCacheEntries($oldFileIdsToDelete);
			}
		} else {
			// No matching fileId, delete the orphaned preview files themselves.
			try {
				$folder = $this->appData->getFolder($internalPath);
				$this->connection->beginTransaction();
				foreach ($folder->getDirectoryListing() as $file) {
					$file->delete();
				}
				$this->connection->commit();
			} catch (NotFoundException) {
				// Folder already gone, nothing to clean up.
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
			->andWhere($qb->expr()->eq('storage', $qb->createNamedParameter($storageId)))
			->setMaxResults(1);
		$cursor = $qb->executeQuery();
		$hasChild = $cursor->fetchOne() !== false;
		$cursor->closeCursor();

		return $hasChild;
	}
}
