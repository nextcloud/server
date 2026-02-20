<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

use OC\Files\SimpleFS\SimpleFile;
use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\Storage\StorageFactory;
use OCP\DB\Exception;
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
				$this->logger->error('Unable to import old preview at path: ' . $path);
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
				} catch (Exception) {
					// We already have this preview in the preview table, skip
					$qb->delete('filecache')
						->where($qb->expr()->eq('fileid', $qb->createNamedParameter($file->getId())))
						->hintShardKey('storage', $this->rootFolder->getMountPoint()->getNumericStorageId())
						->executeStatement();
					continue;
				}

				try {
					$this->storageFactory->migratePreview($preview, $file);
					$qb = $this->connection->getQueryBuilder();
					$qb->delete('filecache')
						->where($qb->expr()->eq('fileid', $qb->createNamedParameter($file->getId())))
						->hintShardKey('storage', $this->rootFolder->getMountPoint()->getNumericStorageId())
						->executeStatement();
					// Do not call $file->delete() as this will also delete the file from the file system
				} catch (\Exception $e) {
					$this->previewMapper->delete($preview);
					throw $e;
				}

				$previews[] = $preview;
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

	private function deleteFolder(string $path): void {
		$current = $path;

		while (true) {
			$appDataPath = $this->previewRootPath . $current;
			$qb = $this->connection->getQueryBuilder();
			$qb->delete('filecache')
				->where($qb->expr()->eq('path_hash', $qb->createNamedParameter(md5($appDataPath))))
				->hintShardKey('storage', $this->rootFolder->getMountPoint()->getNumericStorageId())
				->executeStatement();

			$current = dirname($current);
			if ($current === '/' || $current === '.' || $current === '') {
				break;
			}

			$folder = $this->appData->getFolder($current);
			if (count($folder->getDirectoryListing()) !== 0) {
				break;
			}
		}
	}
}
