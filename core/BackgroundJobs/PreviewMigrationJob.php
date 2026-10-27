<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OC\Core\AppInfo\ConfigLexicon;
use OC\Preview\PreviewMigrationService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
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
		private readonly LoggerInterface $logger,
		private readonly IDBConnection $connection,
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

		$maxParallelJobs = $this->appConfig->getValueInt('core', ConfigLexicon::PREVIEW_MIGRATION_MAX_PARALLEL_JOBS);
		if ($maxParallelJobs > 0 && $this->countOtherRunningJobs() >= $maxParallelJobs) {
			$this->logger->debug('Preview migration of partition {partition} postponed: {max} jobs are already running.', [
				'partition' => $partition,
				'max' => $maxParallelJobs,
			]);
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

		$previewRootId = $storage->getCache()->getId(rtrim($this->previewRootPath, '/'));
		if ($previewRootId === -1) {
			$this->logger->warning('Preview migration skipped: no preview root found at "{path}" on storage "{storageId}".', [
				'path' => $this->previewRootPath,
				'storageId' => $storage->getId(),
			]);
			return true;
		}

		$startTime = time();
		$foldersToVisit = [['id' => $previewRootId, 'name' => '', 'depth' => 0]];
		$foldersToMigrate = [];
		// Folders without preview files, by depth; removed once their children are gone.
		$emptyFolders = [];

		while ($foldersToVisit !== []) {
			$folders = array_filter(
				array_splice($foldersToVisit, -self::BATCH_SIZE),
				fn (array $folder): bool => $folder['depth'] !== 1 || $this->belongsToPartition($folder['name'], $partition),
			);
			$children = $this->migrationService->getFolderChildren(array_column($folders, 'id'));

			foreach ($folders as $folder) {
				foreach ($children[$folder['id']]['folders'] as $subFolder) {
					$foldersToVisit[] = [...$subFolder, 'depth' => $folder['depth'] + 1];
				}

				$files = $children[$folder['id']]['files'];
				if ($files !== [] && ctype_digit($folder['name'])) {
					$foldersToMigrate[] = [
						'fileId' => (int)$folder['name'],
						'folderId' => $folder['id'],
						'flat' => $folder['depth'] === 1,
						'entries' => $files,
					];
				} elseif ($folder['depth'] > 0) {
					$emptyFolders[$folder['depth']][] = $folder['id'];
				}
			}

			if (count($foldersToMigrate) >= self::BATCH_SIZE) {
				$this->migrationService->migrateFolders($foldersToMigrate);
				$foldersToMigrate = [];

				if (time() - $startTime > 3600) {
					return false;
				}
			}
		}

		$this->migrationService->migrateFolders($foldersToMigrate);
		$this->migrationService->deleteEmptyFolders($emptyFolders);

		return true;
	}

	/**
	 * Count the other preview migration jobs currently reserved by a cron worker,
	 * using the same staleness window as {@see \OCP\BackgroundJob\IJobList::hasReservedJob()}.
	 */
	private function countOtherRunningJobs(): int {
		$qb = $this->connection->getQueryBuilder();
		$qb->select($qb->func()->count('*'))
			->from('jobs')
			->where($qb->expr()->eq('class', $qb->createNamedParameter(self::class)))
			->andWhere($qb->expr()->gt('reserved_at', $qb->createNamedParameter($this->time->getTime() - 6 * 3600, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->neq('id', $qb->createNamedParameter((int)$this->getId(), IQueryBuilder::PARAM_INT)));
		$result = $qb->executeQuery();
		$count = (int)$result->fetchOne();
		$result->closeCursor();
		return $count;
	}

	private function belongsToPartition(string $folderName, int $partition): bool {
		if (ctype_digit($folderName)) {
			return ((int)$folderName % self::PARTITIONS) === $partition;
		}

		if (strlen($folderName) === 1 && ctype_xdigit($folderName)) {
			return (hexdec($folderName) % self::PARTITIONS) === $partition;
		}

		return $partition === 0;
	}
}
