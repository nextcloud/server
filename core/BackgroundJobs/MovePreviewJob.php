<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OC\Files\SimpleFS\SimpleFile;
use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\Storage\StorageFactory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use OCP\DB\IResult;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Snowflake\IGenerator;
use Override;
use Psr\Log\LoggerInterface;

class MovePreviewJob extends TimedJob {
	private IAppData $appData;
	private string $previewRootPath;

	public function __construct(
		ITimeFactory $time,
		private readonly IAppConfig $appConfig,
		private readonly IConfig $config,
		private readonly PreviewMapper $previewMapper,
		private readonly StorageFactory $storageFactory,
		private readonly IDBConnection $connection,
		private readonly IRootFolder $rootFolder,
		private readonly IMimeTypeDetector $mimeTypeDetector,
		private readonly IMimeTypeLoader $mimeTypeLoader,
		private readonly LoggerInterface $logger,
		private readonly IGenerator $generator,
		IAppDataFactory $appDataFactory,
	) {
		parent::__construct($time);

		$this->appData = $appDataFactory->get('preview');
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->setInterval(24 * 60 * 60);
		$this->previewRootPath = 'appdata_' . $this->config->getSystemValueString('instanceid') . '/preview/';
	}

	#[Override]
	protected function run(mixed $argument): void {
		if ($this->appConfig->getValueBool('core', 'previewMovedDone')) {
			return;
		}

		$startTime = time();
		while (true) {
			$qb = $this->connection->getQueryBuilder();
			$qb->select('path')
				->from('filecache')
				// Hierarchical preview folder structure
				->where($qb->expr()->like('path', $qb->createNamedParameter($this->previewRootPath . '%/%/%/%/%/%/%/%/%')))
				// Legacy flat preview folder structure
				->orWhere($qb->expr()->like('path', $qb->createNamedParameter($this->previewRootPath . '%/%.%')))
				->hintShardKey('storage', $this->rootFolder->getMountPoint()->getNumericStorageId())
				->setMaxResults(100);

			$result = $qb->executeQuery();
			$foundPreviews = $this->processQueryResult($result);

			if (!$foundPreviews) {
				break;
			}

			// Stop if execution time is more than one hour.
			if (time() - $startTime > 3600) {
				return;
			}
		}

		$this->appConfig->setValueBool('core', 'previewMovedDone', true);
	}

	private function processQueryResult(IResult $result): bool {
		$foundPreview = false;
		$fileIds = [];
		$flatFileIds = [];
		while ($row = $result->fetchAssociative()) {
			$pathSplit = explode('/', $row['path']);
			assert(count($pathSplit) >= 2);
			$fileId = (int)$pathSplit[count($pathSplit) - 2];
			if (count($pathSplit) === 11) {
				// Hierarchical structure
				if (!in_array($fileId, $fileIds)) {
					$fileIds[] = $fileId;
				}
			} else {
				// Flat structure
				if (!in_array($fileId, $flatFileIds)) {
					$flatFileIds[] = $fileId;
				}
			}
			$foundPreview = true;
		}

		foreach ($fileIds as $fileId) {
			$this->processPreviews($fileId, flatPath: false);
		}

		foreach ($flatFileIds as $fileId) {
			$this->processPreviews($fileId, flatPath: true);
		}
		return $foundPreview;
	}

	/**
	 * @param array<string|int, string[]> $previewFolders
	 */
	private function processPreviews(int $fileId, bool $flatPath): void {
		$internalPath = $this->getInternalFolder((string)$fileId, $flatPath);
		$folder = $this->appData->getFolder($internalPath);

		/**
		 * @var list<array{file: SimpleFile, preview: Preview}> $previewFiles
		 */
		$previewFiles = [];

		foreach ($folder->getDirectoryListing() as $previewFile) {
			$path = $fileId . '/' . $previewFile->getName();
			/** @var SimpleFile $previewFile */
			$preview = Preview::fromPath($path, $this->mimeTypeDetector);
			$preview->setId($this->generator->nextId());
			if (!$preview) {
				$this->logger->error('Unable to import old preview at path.');
				continue;
			}
			$preview->setSize($previewFile->getSize());
			$preview->setMtime($previewFile->getMtime());
			$preview->setOldFileId($previewFile->getId());
			$preview->setEncrypted(false);

			$previewFiles[] = [
				'file' => $previewFile,
				'preview' => $preview,
			];
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->select('storage', 'etag', 'mimetype')
			->from('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileId)))
			->setMaxResults(1);

		$result = $qb->executeQuery();
		$result = $result->fetchAllAssociative();

		if (count($result) > 0) {
			foreach ($previewFiles as $previewFile) {
				/** @var Preview $preview */
				$preview = $previewFile['preview'];
				/** @var SimpleFile $file */
				$file = $previewFile['file'];
				$preview->setStorageId($result[0]['storage']);
				$preview->setEtag($result[0]['etag']);
				$preview->setSourceMimeType($this->mimeTypeLoader->getMimetypeById((int)$result[0]['mimetype']));
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
	}

	public static function getInternalFolder(string $name, bool $flatPath): string {
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
