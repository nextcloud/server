<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\Storage\StorageFactory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IPreview;

class MovePreviewJob extends TimedJob {
	private IAppData $appData;

	public function __construct(
		ITimeFactory $time,
		private IAppConfig $appConfig,
		private PreviewMapper $previewMapper,
		private StorageFactory $storageFactory,
		private IDBConnection $connection,
		IAppDataFactory $appDataFactory,
	) {
		parent::__construct($time);

		$this->appData = $appDataFactory->get('preview');
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->setInterval(24 * 60 * 60);
	}

	protected function run(mixed $argument): void {
		try {
			$this->doRun($argument);
		} catch (\Throwable $exception) {
			echo $exception->getMessage();
			throw $exception;
		}
	}

	private function doRun($argument): void {
		if ($this->appConfig->getValueBool('core', 'previewMovedDone')) {
			//return;
		}

		$emptyHierarchicalPreviewFolders = false;

		$startTime = time();
		while (true) {
			$previewFolders = [];

			// Check new hierarchical preview folders first
			if (!$emptyHierarchicalPreviewFolders) {
				$qb = $this->connection->getQueryBuilder();
				$qb->select('*')
					->from('filecache')
					->where($qb->expr()->like('path', $qb->createNamedParameter('appdata_%/preview/%/%/%/%/%/%/%/%/%')))
					->setMaxResults(100);

				$result = $qb->executeQuery();
				while ($row = $result->fetch()) {
					$pathSplit = explode('/', $row['path']);
					assert(count($pathSplit) >= 2);
					$fileId = $pathSplit[count($pathSplit) - 2];
					$previewFolders[$fileId][] = $row['path'];
				}

				if (!empty($previewFolders)) {
					$this->processPreviews($previewFolders, false);
					continue;
				}
			}

			// And then the flat preview folder (legacy)
			$emptyHierarchicalPreviewFolders = true;
			$qb = $this->connection->getQueryBuilder();
			$qb->select('*')
				->from('filecache')
				->where($qb->expr()->like('path', $qb->createNamedParameter('appdata_%/preview/%/%.jpg')))
				->setMaxResults(100);

			$result = $qb->executeQuery();
			while ($row = $result->fetch()) {
				$pathSplit = explode('/', $row['path']);
				assert(count($pathSplit) >= 2);
				$fileId = $pathSplit[count($pathSplit) - 2];
				array_pop($pathSplit);
				$path = implode('/', $pathSplit);
				if (!isset($previewFolders[$fileId])) {
					$previewFolders[$fileId] = [];
				}
				if (!in_array($path, $previewFolders[$fileId])) {
					$previewFolders[$fileId][] = $path;
				}
			}

			if (empty($previewFolders)) {
				break;
			} else {
				$this->processPreviews($previewFolders, true);
			}

			// Stop if execution time is more than one hour.
			if (time() - $startTime > 3600) {
				return;
			}
		}

		// Delete any left over preview directory
		$this->appData->getFolder('.')->delete();
		$this->appConfig->setValueBool('core', 'previewMovedDone', true);
	}

	/**
	 * @param array<string, string[]> $previewFolders
	 */
	private function processPreviews(array $previewFolders, bool $simplePaths): void {
		foreach ($previewFolders as $fileId => $previewFolder) {
			$internalPath = $this->getInternalFolder((string)$fileId, $simplePaths);
			$folder = $this->appData->getFolder($internalPath);

			/**
			 * @var list<array{file: ISimpleFile, width: int, height: int, crop: bool, max: bool, extension: string, mtime: int, size: int}> $previewFiles
			 */
			$previewFiles = [];

			foreach ($folder->getDirectoryListing() as $previewFile) {
				[0 => $baseName, 1 => $extension] = explode('.', $previewFile->getName());
				$nameSplit = explode('-', $baseName);

				// TODO VERSION/PREFIX extraction

				$width = $nameSplit[0];
				$height = $nameSplit[1];

				if (isset($nameSplit[2])) {
					$crop = $nameSplit[2] === 'crop';
					$max = $nameSplit[2] === 'max';
				}

				$previewFiles[] = [
					'file' => $previewFile,
					'width' => $width,
					'height' => $height,
					'crop' => $crop,
					'max' => $max,
					'extension' => $extension,
					'size' => $previewFile->getSize(),
					'mtime' => $previewFile->getMTime(),
				];
			}

			$qb = $this->connection->getQueryBuilder();
			$qb->select('*')
				->from('filecache')
				->where($qb->expr()->like('fileid', $qb->createNamedParameter($fileId)));

			$result = $qb->executeQuery();
			$result = $result->fetchAll();

			if (count($result) > 0) {
				foreach ($previewFiles as $previewFile) {
					$preview = new Preview();
					$preview->setFileId((int)$fileId);
					$preview->setStorageId($result[0]['storage']);
					$preview->setEtag($result[0]['etag']);
					$preview->setMtime($previewFile['mtime']);
					$preview->setWidth($previewFile['width']);
					$preview->setHeight($previewFile['height']);
					$preview->setCrop($previewFile['crop']);
					$preview->setIsMax($previewFile['max']);
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
						$this->storageFactory->migratePreview($preview, $previewFile['file']);
						$previewFile['file']->delete();
					} catch (Exception $e) {
						$this->previewMapper->delete($preview);
						throw $e;
					}

				}
			}

			$this->deleteFolder($internalPath, $folder);
		}
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
