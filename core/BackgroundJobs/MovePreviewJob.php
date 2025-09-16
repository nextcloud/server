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
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IPreview;

class MovePreviewJob extends TimedJob {
	private IAppData $appData;

	public function __construct(
		ITimeFactory $time,
		private readonly IAppConfig $appConfig,
		private readonly PreviewMapper $previewMapper,
		private readonly StorageFactory $storageFactory,
		private readonly IDBConnection $connection,
		private readonly IRootFolder $rootFolder,
		IAppDataFactory $appDataFactory,
	) {
		parent::__construct($time);

		$this->appData = $appDataFactory->get('preview');
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->setInterval(24 * 60 * 60);
	}

	protected function run(mixed $argument): void {
		if ($this->appConfig->getValueBool('core', 'previewMovedDone')) {
			return;
		}

		$emptyHierarchicalPreviewFolders = false;

		$startTime = time();
		while (true) {
			// Check new hierarchical preview folders first
			if (!$emptyHierarchicalPreviewFolders) {
				$qb = $this->connection->getQueryBuilder();
				$qb->select('*')
					->from('filecache')
					->where($qb->expr()->like('path', $qb->createNamedParameter('appdata_%/preview/%/%/%/%/%/%/%/%/%')))
					->hintShardKey('storage', $this->rootFolder->getMountPoint()->getNumericStorageId())
					->setMaxResults(100);

				$result = $qb->executeQuery();
				while ($row = $result->fetch()) {
					$pathSplit = explode('/', $row['path']);
					assert(count($pathSplit) >= 2);
					$fileId = $pathSplit[count($pathSplit) - 2];
					$this->processPreviews($fileId, false);
				}
			}

			// And then the flat preview folder (legacy)
			$emptyHierarchicalPreviewFolders = true;
			$qb = $this->connection->getQueryBuilder();
			$qb->select('*')
				->from('filecache')
				->where($qb->expr()->like('path', $qb->createNamedParameter('appdata_%/preview/%/%.%')))
				->hintShardKey('storage', $this->rootFolder->getMountPoint()->getNumericStorageId())
				->setMaxResults(100);

			$result = $qb->executeQuery();
			while ($row = $result->fetch()) {
				$pathSplit = explode('/', $row['path']);
				assert(count($pathSplit) >= 2);
				$fileId = $pathSplit[count($pathSplit) - 2];
				array_pop($pathSplit);
				$path = implode('/', $pathSplit);
				$this->processPreviews($fileId, true);
			}

			// Stop if execution time is more than one hour.
			if (time() - $startTime > 3600) {
				return;
			}
		}

		try {
			// Delete any leftover preview directory
			$this->appData->getFolder('.')->delete();
		} catch (NotFoundException) {
			// ignore
		}
		$this->appConfig->setValueBool('core', 'previewMovedDone', true);
	}

	/**
	 * @param array<string|int, string[]> $previewFolders
	 */
	private function processPreviews(int|string $fileId, bool $simplePaths): void {
		$internalPath = $this->getInternalFolder((string)$fileId, $simplePaths);
		$folder = $this->appData->getFolder($internalPath);

		/**
		 * @var list<array{
		 *     file: SimpleFile, width: int, height: int, crop: bool, max: bool, extension: string, mtime: int, size: int, version: ?int
		 * }> $previewFiles
		 */
		$previewFiles = [];

		foreach ($folder->getDirectoryListing() as $previewFile) {
			/** @var SimpleFile $previewFile */
			[0 => $baseName, 1 => $extension] = explode('.', $previewFile->getName());
			$nameSplit = explode('-', $baseName);

			$offset = 0;
			$version = null;
			if (count($nameSplit) === 4 || (count($nameSplit) === 3 && is_numeric($nameSplit[2]))) {
				$offset = 1;
				$version = (int)$nameSplit[0];
			}

			$width = (int)$nameSplit[$offset + 0];
			$height = (int)$nameSplit[$offset + 1];

			$crop = false;
			$max = false;
			if (isset($nameSplit[$offset + 2])) {
				$crop = $nameSplit[$offset + 2] === 'crop';
				$max = $nameSplit[$offset + 2] === 'max';
			}

			$previewFiles[] = [
				'file' => $previewFile,
				'width' => $width,
				'height' => $height,
				'crop' => $crop,
				'version' => $version,
				'max' => $max,
				'extension' => $extension,
				'size' => $previewFile->getSize(),
				'mtime' => $previewFile->getMTime(),
			];
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileId)))
			->setMaxResults(1);

		$result = $qb->executeQuery();
		$result = $result->fetchAll();

		if (count($result) > 0) {
			foreach ($previewFiles as $previewFile) {
				$preview = new Preview();
				$preview->setFileId((int)$fileId);
				/** @var SimpleFile $file */
				$file = $previewFile['file'];
				$preview->setOldFileId($file->getId());
				$preview->setStorageId($result[0]['storage']);
				$preview->setEtag($result[0]['etag']);
				$preview->setMtime($previewFile['mtime']);
				$preview->setWidth($previewFile['width']);
				$preview->setHeight($previewFile['height']);
				$preview->setCropped($previewFile['crop']);
				$preview->setVersion($previewFile['version']);
				$preview->setMax($previewFile['max']);
				$preview->setEncrypted(false);
				$preview->setMimetype(match ($previewFile['extension']) {
					'png' => IPreview::MIMETYPE_PNG,
					'webp' => IPreview::MIMETYPE_WEBP,
					'gif' => IPreview::MIMETYPE_GIF,
					default => IPreview::MIMETYPE_JPEG,
				});
				$preview->setSize($previewFile['size']);
				try {
					$preview = $this->previewMapper->insert($preview);
				} catch (Exception $e) {
					// We already have this preview in the preview table, skip
					continue;
				}

				try {
					$this->storageFactory->migratePreview($preview, $file);
					$qb->delete('filecache')
						->where($qb->expr()->eq('fileid', $qb->createNamedParameter($file->getId())))
						->executeStatement();
					// Do not call $file->delete() as this will also delete the file from the file system
				} catch (\Exception $e) {
					$this->previewMapper->delete($preview);
					throw $e;
				}
			}
		}

		$this->deleteFolder($internalPath, $folder);
	}

	public static function getInternalFolder(string $name, bool $simplePaths): string {
		if ($simplePaths) {
			return '/' . $name;
		}
		return implode('/', str_split(substr(md5($name), 0, 7))) . '/' . $name;
	}

	private function deleteFolder(string $path, ISimpleFolder $folder): void {
		$folder->delete();

		$current = $path;

		while (true) {
			$current = dirname($current);
			if ($current === '/' || $current === '.' || $current === '') {
				break;
			}


			$folder = $this->appData->getFolder($current);
			if (count($folder->getDirectoryListing()) !== 0) {
				break;
			}
			$folder->delete();
		}
	}
}
