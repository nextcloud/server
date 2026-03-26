<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Storage;

use LogicException;
use OC;
use OC\Files\SimpleFS\SimpleFile;
use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use Override;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LocalPreviewStorage implements IPreviewStorage {
	private const SCAN_BATCH_SIZE = 1000;

	public function __construct(
		private readonly IConfig $config,
		private readonly PreviewMapper $previewMapper,
		private readonly IAppConfig $appConfig,
		private readonly IDBConnection $connection,
		private readonly IMimeTypeDetector $mimeTypeDetector,
		private readonly LoggerInterface $logger,
		private readonly IMimeTypeLoader $mimeTypeLoader,
		private readonly IRootFolder $rootFolder,
	) {
	}

	#[Override]
	public function writePreview(Preview $preview, mixed $stream): int {
		$previewPath = $this->constructPath($preview);
		$this->createParentFiles($previewPath);
		return file_put_contents($previewPath, $stream);
	}

	#[Override]
	public function readPreview(Preview $preview): mixed {
		$previewPath = $this->constructPath($preview);
		$resource = @fopen($previewPath, 'r');
		if ($resource === false) {
			throw new NotFoundException('Unable to open preview stream at ' . $previewPath);
		}
		return $resource;
	}

	#[Override]
	public function deletePreview(Preview $preview): void {
		$previewPath = $this->constructPath($preview);
		if (!@unlink($previewPath) && is_file($previewPath)) {
			throw new NotPermittedException('Unable to delete preview at ' . $previewPath);
		}
	}

	public function getRootFolder(): string {
		return $this->config->getSystemValueString('datadirectory', OC::$SERVERROOT . '/data');
	}

	public function getPreviewRootFolder(): string {
		return $this->getRootFolder() . '/' . $this->rootFolder->getAppDataDirectoryName() . '/preview/';
	}

	private function constructPath(Preview $preview): string {
		return $this->getPreviewRootFolder() . implode('/', str_split(substr(md5((string)$preview->getFileId()), 0, 7))) . '/' . $preview->getFileId() . '/' . $preview->getName();
	}

	private function createParentFiles(string $path): void {
		$dirname = dirname($path);
		if (!is_dir($dirname)) {
			mkdir($dirname, recursive: true);
		}
		if (!is_dir($dirname)) {
			throw new NotPermittedException("Unable to create directory '$dirname'");
		}
	}

	#[Override]
	public function migratePreview(Preview $preview, SimpleFile $file): void {
		// legacy flat directory
		$sourcePath = $this->getPreviewRootFolder() . $preview->getFileId() . '/' . $preview->getName();
		if (!file_exists($sourcePath)) {
			return;
		}

		$destinationPath = $this->constructPath($preview);
		if (file_exists($destinationPath)) {
			@unlink($sourcePath); // We already have a new preview, just delete the old one
			return;
		}

		$this->createParentFiles($destinationPath);
		$ok = rename($sourcePath, $destinationPath);
		if (!$ok) {
			throw new LogicException('Failed to move ' . $sourcePath . ' to ' . $destinationPath);
		}
	}

	#[Override]
	public function scan(): int {
		$checkForFileCache = !$this->appConfig->getValueBool('core', 'previewMovedDone');

		if (!file_exists($this->getPreviewRootFolder())) {
			return 0;
		}

		$scanner = new RecursiveDirectoryIterator($this->getPreviewRootFolder());
		$previewsFound = 0;

		/**
		 * Use an associative array keyed by path for O(1) lookup instead of
		 * the O(n) in_array() the original code used.
		 *
		 * @var array<string, true> $skipPaths
		 */
		$skipPaths = [];

		/**
		 * Pending previews grouped by fileId.  A single original file can have
		 * many preview variants (different sizes/formats), so we group them to
		 * issue one filecache lookup per original file rather than one per
		 * preview variant.
		 *
		 * @var array<int, list<array{preview: Preview, filePath: string, realPath: string}>> $pendingByFileId
		 */
		$pendingByFileId = [];

		/**
		 * path_hash => realPath for legacy filecache entries that need to be
		 * cleaned up. Only populated when $checkForFileCache is true.
		 *
		 * @var array<string, string> $pendingPathHashes
		 */
		$pendingPathHashes = [];
		$pendingCount = 0;

		foreach (new RecursiveIteratorIterator($scanner) as $file) {
			if (!$file->isFile()) {
				continue;
			}

			$filePath = $file->getPathname();
			if (isset($skipPaths[$filePath])) {
				continue;
			}

			$preview = Preview::fromPath($filePath, $this->mimeTypeDetector);
			if ($preview === false) {
				$this->logger->error('Unable to parse preview information for ' . $file->getRealPath());
				continue;
			}

			$preview->setSize($file->getSize());
			$preview->setMtime($file->getMtime());
			$preview->setEncrypted(false);

			$realPath = $file->getRealPath();
			$pendingByFileId[$preview->getFileId()][] = [
				'preview' => $preview,
				'filePath' => $filePath,
				'realPath' => $realPath,
			];
			$pendingCount++;

			if ($checkForFileCache) {
				$relativePath = str_replace($this->getRootFolder() . '/', '', $realPath);
				$pendingPathHashes[md5($relativePath)] = $realPath;
			}

			if ($pendingCount >= self::SCAN_BATCH_SIZE) {
				$this->connection->beginTransaction();
				try {
					$previewsFound += $this->processScanBatch($pendingByFileId, $pendingPathHashes, $checkForFileCache, $skipPaths);
					$this->connection->commit();
				} catch (\Exception $e) {
					$this->connection->rollBack();
					$this->logger->error($e->getMessage(), ['exception' => $e]);
					throw $e;
				}
				$pendingByFileId = [];
				$pendingPathHashes = [];
				$pendingCount = 0;
			}
		}

		if ($pendingCount > 0) {
			$this->connection->beginTransaction();
			try {
				$previewsFound += $this->processScanBatch($pendingByFileId, $pendingPathHashes, $checkForFileCache, $skipPaths);
				$this->connection->commit();
			} catch (\Exception $e) {
				$this->connection->rollBack();
				$this->logger->error($e->getMessage(), ['exception' => $e]);
				throw $e;
			}
		}

		return $previewsFound;
	}

	/**
	 * Process one batch of preview files collected during scan().
	 *
	 * @param array<int, list<array{preview: Preview, filePath: string, realPath: string}>> $pendingByFileId
	 * @param array<string, string> $pendingPathHashes path_hash => realPath
	 * @param array<string, true> $skipPaths Modified in place: newly-moved paths are added so the outer iterator skips them.
	 */
	private function processScanBatch(
		array $pendingByFileId,
		array $pendingPathHashes,
		bool $checkForFileCache,
		array &$skipPaths,
	): int {
		$filecacheByFileId = $this->fetchFilecacheByFileIds(array_keys($pendingByFileId));
		$legacyByPathHash = [];
		if ($checkForFileCache && $pendingPathHashes !== []) {
			$legacyByPathHash = $this->fetchFilecacheByPathHashes(array_keys($pendingPathHashes));
		}

		$previewsFound = 0;
		foreach ($pendingByFileId as $fileId => $items) {
			if (!isset($filecacheByFileId[$fileId])) {
				// Original file has been deleted – clean up all its previews.
				foreach ($items as $item) {
					$this->logger->warning('Original file ' . $fileId . ' was not found. Deleting preview at ' . $item['realPath']);
					@unlink($item['realPath']);
				}
				continue;
			}

			$filecacheRow = $filecacheByFileId[$fileId];
			foreach ($items as $item) {
				$preview = $item['preview'];

				if ($checkForFileCache) {
					$relativePath = str_replace($this->getRootFolder() . '/', '', $item['realPath']);
					$pathHash = md5($relativePath);
					if (isset($legacyByPathHash[$pathHash])) {
						$legacyRow = $legacyByPathHash[$pathHash];
						$qb = $this->connection->getQueryBuilder();
						$qb->delete('filecache')
							->where($qb->expr()->eq('fileid', $qb->createNamedParameter($legacyRow['fileid'])))
							->andWhere($qb->expr()->eq('storage', $qb->createNamedParameter($legacyRow['storage'])))
							->executeStatement();
						$this->deleteParentsFromFileCache((int)$legacyRow['parent'], (int)$legacyRow['storage']);
					}
				}

				$preview->setStorageId((int)$filecacheRow['storage']);
				$preview->setEtag($filecacheRow['etag']);
				$preview->setSourceMimetype($this->mimeTypeLoader->getMimetypeById((int)$filecacheRow['mimetype']));
				$preview->generateId();

				$this->connection->beginTransaction();
				try {
					$this->previewMapper->insert($preview);
					$this->connection->commit();
				} catch (Exception $e) {
					$this->connection->rollBack();
					if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
						throw $e;
					}
				}

				// Move old flat preview to new nested directory format.
				$dirName = str_replace($this->getPreviewRootFolder(), '', $item['filePath']);
				if (preg_match('/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9]+/', $dirName) !== 1) {
					$previewPath = $this->constructPath($preview);
					$this->createParentFiles($previewPath);
					$ok = rename($item['realPath'], $previewPath);
					if (!$ok) {
						throw new LogicException('Failed to move ' . $item['realPath'] . ' to ' . $previewPath);
					}
					// Mark the destination so the outer iterator skips it if it encounters the path later.
					$skipPaths[$previewPath] = true;
				}

				$previewsFound++;
			}
		}

		return $previewsFound;
	}

	/**
	 * Bulk-fetch filecache rows for a set of fileIds.
	 *
	 * @param int[] $fileIds
	 */
	private function fetchFilecacheByFileIds(array $fileIds): array {
		if (empty($fileIds)) {
			return [];
		}

		$result = [];
		$qb = $this->connection->getQueryBuilder();
		$qb->select('fileid', 'storage', 'etag', 'mimetype')
			->from('filecache');
		foreach (array_chunk($fileIds, 1000) as $chunk) {
			$qb->andWhere(
				$qb->expr()->in('fileid', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY))
			);
		}
		$rows = $qb->runAcrossAllShards()
			->executeQuery();
		while ($row = $rows->fetchAssociative()) {
			$result[(int)$row['fileid']] = $row;
		}
		$rows->closeCursor();
		return $result;
	}

	/**
	 * Bulk-fetch filecache rows for a set of path_hashes (legacy migration).
	 *
	 * @param string[] $pathHashes
	 */
	private function fetchFilecacheByPathHashes(array $pathHashes): array {
		if (empty($pathHashes)) {
			return [];
		}

		$result = [];
		$qb = $this->connection->getQueryBuilder();
		$qb->select('fileid', 'storage', 'etag', 'mimetype', 'parent', 'path_hash')
			->from('filecache');
		foreach (array_chunk($pathHashes, 1000) as $chunk) {
			$qb->andWhere(
				$qb->expr()->in('path_hash', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY))
			);
		}
		$rows = $qb->runAcrossAllShards()
			->executeQuery();
		while ($row = $rows->fetchAssociative()) {
			$result[$row['path_hash']] = $row;
		}
		$rows->closeCursor();
		return $result;
	}

	/**
	 * Recursive method that deletes the folder and its parent folders if it's not
	 * empty.
	 */
	private function deleteParentsFromFileCache(int $folderId, int $storageId): void {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select('fileid', 'path', 'storage', 'parent')
			->from('filecache')
			->where($qb->expr()->eq('parent', $qb->createNamedParameter($folderId)))
			->setMaxResults(1)
			->runAcrossAllShards()
			->executeQuery();
		$row = $result->fetchAssociative();
		$result->closeCursor();

		if ($row !== false) {
			// there are other files in the directory, don't delete yet
			return;
		}

		// Get new parent
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select('fileid', 'path', 'parent')
			->from('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($folderId)))
			->andWhere($qb->expr()->eq('storage', $qb->createNamedParameter($storageId)))
			->setMaxResults(1)
			->executeQuery();
		$row = $result->fetchAssociative();
		$result->closeCursor();
		if ($row !== false) {
			$parentFolderId = (int)$row['parent'];

			$qb = $this->connection->getQueryBuilder();
			$qb->delete('filecache')
				->where($qb->expr()->eq('fileid', $qb->createNamedParameter($folderId)))
				->andWhere($qb->expr()->eq('storage', $qb->createNamedParameter($storageId)))
				->executeStatement();

			$this->deleteParentsFromFileCache($parentFolderId, $storageId);
		}
	}
}
