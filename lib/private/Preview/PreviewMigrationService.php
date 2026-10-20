<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

use OC\Files\Cache\CacheEntry;
use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\SimpleFS\SimpleFile;
use OC\Files\Storage\Wrapper\Wrapper;
use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\Storage\StorageFactory;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\FileInfo;
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
		$internalPath = self::getInternalFolder((string)$fileId, $flatPath);

		if ($entries === null) {
			try {
				$entries = $this->appData->getFolder($internalPath)->getDirectoryListing();
			} catch (NotFoundException) {
				return [];
			}
		}

		$source = $this->getSourceFiles([$fileId])[$fileId] ?? null;
		if ($source === null) {
			$this->deleteOrphanedPreviews($internalPath, $entries);
			$this->deleteFolder($internalPath);
			return [];
		}

		$previews = [];
		$oldFileIdsToDelete = [];
		try {
			foreach ($this->createPreviews($fileId, $entries, $source) as $preview) {
				// Commit the storage migration and the insert together, one commit per preview.
				// Do not delete the old file via a Node afterwards, as that would also
				// delete it from the file system; only its filecache row is stale.
				$this->connection->beginTransaction();
				try {
					$this->storageFactory->migratePreviews([$preview]);
					$previews[] = $this->previewMapper->insert($preview);
					$this->connection->commit();
				} catch (Exception $e) {
					$this->connection->rollBack();
					if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
						throw $e;
					}
					// We already have this preview in the preview table, skip
				} catch (\Exception $e) {
					$this->connection->rollBack();
					throw $e;
				}
				$oldFileIdsToDelete[] = $preview->getOldFileId();
			}
		} finally {
			$this->deleteOldFileCacheEntries($oldFileIdsToDelete);
		}

		$this->deleteFolder($internalPath);

		return $previews;
	}

	/**
	 * Migrate the previews of many legacy preview folders at once.
	 *
	 * The inserts of the whole batch share one transaction. If any of them fails,
	 * e.g. because a preview was migrated concurrently, the batch is rolled back
	 * and each folder is retried individually with migrateFileId().
	 *
	 * @param list<array{fileId: int, folderId: int, flat: bool, entries: list<ICacheEntry>}> $folders
	 */
	public function migrateFolders(array $folders): void {
		if ($folders === []) {
			return;
		}

		$sources = $this->getSourceFiles(array_column($folders, 'fileId'));
		$previews = [];
		$folderIds = [];
		foreach ($folders as $folder) {
			$source = $sources[$folder['fileId']] ?? null;
			if ($source === null) {
				$this->deleteOrphanedPreviews(self::getInternalFolder((string)$folder['fileId'], $folder['flat']), $folder['entries']);
			} else {
				array_push($previews, ...$this->createPreviews($folder['fileId'], $folder['entries'], $source));
			}
			$folderIds[] = $folder['folderId'];
		}

		$this->connection->beginTransaction();
		try {
			$this->storageFactory->migratePreviews($previews);
			$this->previewMapper->insertMany($previews);
			$this->deleteOldFileCacheEntries([
				...array_map(static fn (Preview $preview): int => $preview->getOldFileId(), $previews),
				...$folderIds,
			]);
			$this->connection->commit();
			return;
		} catch (\Exception $e) {
			$this->connection->rollBack();
			$this->logger->info('Batch preview migration failed, retrying folder by folder.', ['exception' => $e]);
		}

		foreach ($folders as $folder) {
			if (!isset($sources[$folder['fileId']])) {
				continue;
			}
			try {
				$this->migrateFileId($folder['fileId'], $folder['flat'], $folder['entries']);
			} catch (\Exception $e) {
				$this->logger->error('Failed to migrate preview with fileId: ' . $folder['fileId'], [
					'exception' => $e,
				]);
			}
		}
	}

	/**
	 * List the children of the given folders of the root storage.
	 *
	 * @param list<int> $folderIds
	 * @return array<int, array{folders: list<array{id: int, name: string}>, files: list<ICacheEntry>}> by parent folder id
	 */
	public function getFolderChildren(array $folderIds): array {
		$children = array_fill_keys($folderIds, ['folders' => [], 'files' => []]);
		if ($folderIds === []) {
			return $children;
		}

		$folderMimeTypeId = $this->mimeTypeLoader->getId(FileInfo::MIMETYPE_FOLDER);
		$qb = $this->connection->getQueryBuilder();
		$qb->select('fileid', 'parent', 'name', 'mimetype', 'size', 'mtime')
			->from('filecache')
			->where($qb->expr()->in('parent', $qb->createNamedParameter($folderIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->hintShardKey('storage', $this->rootFolder->getMountPoint()->getNumericStorageId());
		$cursor = $qb->executeQuery();
		while ($row = $cursor->fetchAssociative()) {
			$parent = (int)$row['parent'];
			if ((int)$row['mimetype'] === $folderMimeTypeId) {
				$children[$parent]['folders'][] = ['id' => (int)$row['fileid'], 'name' => $row['name']];
			} else {
				$children[$parent]['files'][] = new CacheEntry($row);
			}
		}
		$cursor->closeCursor();

		return $children;
	}

	/**
	 * Delete the given folders of the root storage that have no children anymore.
	 *
	 * @param array<int, list<int>> $folderIdsByDepth
	 */
	public function deleteEmptyFolders(array $folderIdsByDepth): void {
		// Deepest first, so that a parent is empty once its children are gone.
		krsort($folderIdsByDepth);
		foreach ($folderIdsByDepth as $folderIds) {
			foreach (array_chunk($folderIds, 1000) as $chunk) {
				$qb = $this->connection->getQueryBuilder();
				$qb->selectDistinct('parent')
					->from('filecache')
					->where($qb->expr()->in('parent', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)))
					->hintShardKey('storage', $this->rootFolder->getMountPoint()->getNumericStorageId());
				$nonEmpty = array_map('intval', $qb->executeQuery()->fetchFirstColumn());
				$this->deleteOldFileCacheEntries(array_values(array_diff($chunk, $nonEmpty)));
			}
		}
	}

	/**
	 * @param list<int> $fileIds
	 * @return array<int, array{storage: int, etag: string, mimetype: int}> by file id
	 */
	private function getSourceFiles(array $fileIds): array {
		$sources = [];
		foreach (array_chunk(array_values(array_unique($fileIds)), 1000) as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb->select('fileid', 'storage', 'etag', 'mimetype')
				->from('filecache')
				->where($qb->expr()->in('fileid', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));
			$cursor = $qb->executeQuery();
			while ($row = $cursor->fetchAssociative()) {
				$sources[(int)$row['fileid']] = [
					'storage' => (int)$row['storage'],
					'etag' => (string)$row['etag'],
					'mimetype' => (int)$row['mimetype'],
				];
			}
			$cursor->closeCursor();
		}
		return $sources;
	}

	/**
	 * @param list<ICacheEntry|SimpleFile> $entries
	 * @param array{storage: int, etag: string, mimetype: int} $source
	 * @return list<Preview>
	 */
	private function createPreviews(int $fileId, array $entries, array $source): array {
		$sourceMimeType = $this->mimeTypeLoader->getMimetypeById($source['mimetype']);
		$previews = [];
		foreach ($entries as $entry) {
			$preview = Preview::fromPath($fileId . '/' . $entry->getName(), $this->mimeTypeDetector);
			if ($preview === false) {
				$this->logger->error('Unable to import old preview at path.');
				continue;
			}
			$preview->setSize($entry->getSize());
			$preview->setMtime($entry->getMTime());
			$preview->setOldFileId($entry->getId());
			$preview->setEncrypted(false);
			$preview->setStorageId($source['storage']);
			$preview->setEtag($source['etag']);
			$preview->setSourceMimeType($sourceMimeType);
			$previews[] = $preview;
		}
		return $previews;
	}

	/**
	 * Delete preview files whose source file no longer exists, together with their filecache rows.
	 *
	 * @param list<ICacheEntry|SimpleFile> $entries
	 */
	private function deleteOrphanedPreviews(string $internalPath, array $entries): void {
		$storage = $this->rootFolder->getMountPoint()->getStorage();
		// Delete objects by urn directly, the filecache rows are removed in bulk below.
		$objectStoreStorage = null;
		if ($storage->instanceOfStorage(ObjectStoreStorage::class)) {
			$objectStoreStorage = $storage instanceof Wrapper ? $storage->getInstanceOfStorage(ObjectStoreStorage::class) : $storage;
		}

		$fileIds = [];
		foreach ($entries as $entry) {
			try {
				if ($objectStoreStorage instanceof ObjectStoreStorage) {
					$objectStoreStorage->getObjectStore()->deleteObject($objectStoreStorage->getURN($entry->getId()));
				} else {
					$storage->unlink($this->previewRootPath . $internalPath . '/' . $entry->getName());
				}
			} catch (\Exception $e) {
				// An object that is already gone only leaves its filecache row to remove.
				if ($e->getCode() !== 404) {
					$this->logger->error('Unable to delete orphaned preview at ' . $internalPath . '/' . $entry->getName(), [
						'exception' => $e,
					]);
					continue;
				}
			}
			$fileIds[] = $entry->getId();
		}
		$this->deleteOldFileCacheEntries($fileIds);
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

		// Commit the whole upward walk at once instead of once per ancestor level.
		$this->connection->beginTransaction();
		try {
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
			$this->connection->commit();
		} catch (\Throwable $e) {
			$this->connection->rollBack();
			throw $e;
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
