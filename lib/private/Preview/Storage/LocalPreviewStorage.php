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
		$skipFiles = [];
		foreach (new RecursiveIteratorIterator($scanner) as $file) {
			if ($file->isFile() && !in_array((string)$file, $skipFiles, true)) {
				$preview = Preview::fromPath((string)$file, $this->mimeTypeDetector);
				if ($preview === false) {
					$this->logger->error('Unable to parse preview information for ' . $file->getRealPath());
					continue;
				}
				try {
					$preview->setSize($file->getSize());
					$preview->setMtime($file->getMtime());
					$preview->setEncrypted(false);

					$qb = $this->connection->getQueryBuilder();
					$result = $qb->select('storage', 'etag', 'mimetype')
						->from('filecache')
						->where($qb->expr()->eq('fileid', $qb->createNamedParameter($preview->getFileId())))
						->setMaxResults(1)
						->runAcrossAllShards() // Unavoidable because we can't extract the storage_id from the preview name
						->executeQuery()
						->fetchAssociative();

					if ($result === false) {
						// original file is deleted
						$this->logger->warning('Original file ' . $preview->getFileId() . ' was not found. Deleting preview at ' . $file->getRealPath());
						@unlink($file->getRealPath());
						continue;
					}

					if ($checkForFileCache) {
						$relativePath = str_replace($this->getRootFolder() . '/', '', $file->getRealPath());
						$qb = $this->connection->getQueryBuilder();
						$result2 = $qb->select('fileid', 'storage', 'etag', 'mimetype', 'parent')
							->from('filecache')
							->where($qb->expr()->eq('path_hash', $qb->createNamedParameter(md5($relativePath))))
							->runAcrossAllShards()
							->setMaxResults(1)
							->executeQuery()
							->fetchAssociative();

						if ($result2 !== false) {
							$qb->delete('filecache')
								->where($qb->expr()->eq('fileid', $qb->createNamedParameter($result2['fileid'])))
								->andWhere($qb->expr()->eq('storage', $qb->createNamedParameter($result2['storage'])))
								->executeStatement();
							$this->deleteParentsFromFileCache((int)$result2['parent'], (int)$result2['storage']);
						}
					}

					$preview->setStorageId((int)$result['storage']);
					$preview->setEtag($result['etag']);
					$preview->setSourceMimetype($this->mimeTypeLoader->getMimetypeById((int)$result['mimetype']));
					$preview->generateId();
					// try to insert, if that fails the preview is already in the DB
					$this->previewMapper->insert($preview);

					// Move old flat preview to new format
					$dirName = str_replace($this->getPreviewRootFolder(), '', $file->getPath());
					if (preg_match('/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9]+/', $dirName) !== 1) {
						$previewPath = $this->constructPath($preview);
						$this->createParentFiles($previewPath);
						$ok = rename($file->getRealPath(), $previewPath);
						if (!$ok) {
							throw new LogicException('Failed to move ' . $file->getRealPath() . ' to ' . $previewPath);
						}

						$skipFiles[] = $previewPath;
					}
				} catch (Exception $e) {
					if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
						throw $e;
					}
				}
				$previewsFound++;
			}
		}

		return $previewsFound;
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
			->executeQuery()
			->fetchAssociative();

		if ($result !== false) {
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
			->executeQuery()
			->fetchAssociative();

		if ($result !== false) {
			$parentFolderId = (int)$result['parent'];

			$qb = $this->connection->getQueryBuilder();
			$qb->delete('filecache')
				->where($qb->expr()->eq('fileid', $qb->createNamedParameter($folderId)))
				->andWhere($qb->expr()->eq('storage', $qb->createNamedParameter($storageId)))
				->executeStatement();

			$this->deleteParentsFromFileCache($parentFolderId, $storageId);
		}
	}
}
