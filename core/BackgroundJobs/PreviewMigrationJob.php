<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OC\Files\Cache\CacheEntry;
use OC\Preview\PreviewMigrationService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\FileInfo;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use Override;
use Psr\Log\LoggerInterface;

class PreviewMigrationJob extends TimedJob {
	public const int PARTITIONS = 16;
	private const int BATCH_SIZE = 500;
	private string $previewRootPath;

	public function __construct(
		ITimeFactory $time,
		private readonly IAppConfig $appConfig,
		IConfig $config,
		private readonly IRootFolder $rootFolder,
		private readonly PreviewMigrationService $migrationService,
		private readonly IJobList $jobList,
		private readonly IDBConnection $connection,
		private readonly IMimeTypeLoader $mimeTypeLoader,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->setInterval(62 * 60); // every 62 min as the job takes 60 min
		$this->previewRootPath = 'appdata_' . $config->getSystemValueString('instanceid') . '/preview/';
	}

	#[Override]
	protected function run(mixed $argument): void {
		if ($this->appConfig->getValueBool('core', 'previewMovedDone')) {
			$this->jobList->removeById($this->getId());
			return;
		}

		$partition = (int)($argument['partition'] ?? 0);
		if ($partition < 0 || $partition >= self::PARTITIONS) {
			$this->jobList->removeById($this->getId());
			return;
		}

		if (!$this->runPartition($partition)) {
			return;
		}

		$this->appConfig->setValueBool('core', 'previewMigrationPartition' . $partition, true);
		$this->jobList->removeById($this->getId());
		for ($i = 0; $i < self::PARTITIONS; $i++) {
			if (!$this->appConfig->getValueBool('core', 'previewMigrationPartition' . $i, false)) {
				return;
			}
		}

		$this->appConfig->setValueBool('core', 'previewMovedDone', true);
	}

	private function runPartition(int $partition): bool {
		$storage = $this->rootFolder->getMountPoint()->getStorage();
		if ($storage === null) {
			$this->logger->warning('Preview migration skipped: the root mount point has no storage.');
			return true;
		}

		$cache = $storage->getCache();
		$previewRootId = $cache->getId(rtrim($this->previewRootPath, '/'));
		if ($previewRootId === -1) {
			$this->logger->warning('Preview migration skipped: no preview root found at "{path}" on storage "{storageId}".', [
				'path' => $this->previewRootPath,
				'storageId' => $storage->getId(),
			]);
			return true;
		}

		$startTime = time();
		$storageId = $cache->getNumericStorageId();
		$folderMimeTypeId = $this->mimeTypeLoader->getId(FileInfo::MIMETYPE_FOLDER);
		$foldersToVisit = [[$previewRootId, '', 0]];
		$foldersToMigrate = [];
		// Folders without preview files, by depth; removed once their children are gone.
		$emptyFolders = [];

		while ($foldersToVisit !== []) {
			$folders = [];
			foreach (array_splice($foldersToVisit, -self::BATCH_SIZE) as [$folderId, $folderName, $depth]) {
				if ($depth === 1 && !$this->belongsToPartition($folderName, $partition)) {
					continue;
				}
				$folders[$folderId] = [$folderName, $depth];
			}
			if ($folders === []) {
				continue;
			}

			$entries = array_fill_keys(array_keys($folders), []);
			$qb = $this->connection->getQueryBuilder();
			$qb->select('fileid', 'parent', 'name', 'mimetype', 'size', 'mtime')
				->from('filecache')
				->where($qb->expr()->in('parent', $qb->createNamedParameter(array_keys($folders), IQueryBuilder::PARAM_INT_ARRAY)))
				->hintShardKey('storage', $storageId);
			$cursor = $qb->executeQuery();
			while ($row = $cursor->fetchAssociative()) {
				$parent = (int)$row['parent'];
				if ((int)$row['mimetype'] === $folderMimeTypeId) {
					$foldersToVisit[] = [(int)$row['fileid'], $row['name'], $folders[$parent][1] + 1];
				} else {
					$entries[$parent][] = new CacheEntry($row);
				}
			}
			$cursor->closeCursor();

			foreach ($folders as $folderId => [$folderName, $depth]) {
				if ($entries[$folderId] === [] || !ctype_digit($folderName)) {
					if ($depth > 0) {
						$emptyFolders[$depth][] = $folderId;
					}
					continue;
				}
				$foldersToMigrate[] = ['fileId' => (int)$folderName, 'folderId' => $folderId, 'flat' => $depth === 1, 'entries' => $entries[$folderId]];
			}

			if (count($foldersToMigrate) >= self::BATCH_SIZE) {
				$this->migrateFolders($foldersToMigrate);
				$foldersToMigrate = [];

				if (time() - $startTime > 3600) {
					return false;
				}
			}
		}
		$this->migrateFolders($foldersToMigrate);

		krsort($emptyFolders);
		foreach ($emptyFolders as $folderIds) {
			foreach (array_chunk($folderIds, 1000) as $chunk) {
				$qb = $this->connection->getQueryBuilder();
				$qb->selectDistinct('parent')
					->from('filecache')
					->where($qb->expr()->in('parent', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)))
					->hintShardKey('storage', $storageId);
				$nonEmpty = array_map('intval', $qb->executeQuery()->fetchFirstColumn());
				$this->migrationService->deleteOldFileCacheEntries(array_values(array_diff($chunk, $nonEmpty)));
			}
		}

		return true;
	}

	/**
	 * @param list<array{fileId: int, folderId: int, flat: bool, entries: list<CacheEntry>}> $folders
	 */
	private function migrateFolders(array $folders): void {
		try {
			$this->migrationService->migrateFolders($folders);
		} catch (\Exception $e) {
			$this->logger->error('Failed to migrate previews of file ids: ' . implode(', ', array_column($folders, 'fileId')), [
				'exception' => $e,
			]);
		}
	}

	private function belongsToPartition(string $folderName, int $partition): bool {
		if ($partition < 0 || $partition >= self::PARTITIONS) {
			return false;
		}

		if (ctype_digit($folderName)) {
			return ((int)$folderName % self::PARTITIONS) === $partition;
		}

		if (strlen($folderName) === 1 && ctype_xdigit($folderName)) {
			return (hexdec($folderName) % self::PARTITIONS) === $partition;
		}

		return $partition === 0;
	}
}
