<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OC\Preview\PreviewMigrationService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IConfig;
use Override;
use Psr\Log\LoggerInterface;

class PreviewMigrationJob extends TimedJob {
	public const int PARTITIONS = 16;
	private string $previewRootPath;

	public function __construct(
		ITimeFactory $time,
		private readonly IAppConfig $appConfig,
		IConfig $config,
		private readonly IRootFolder $rootFolder,
		private readonly PreviewMigrationService $migrationService,
		private readonly IJobList $jobList,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->setInterval(24 * 60);
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
		$foldersToVisit = [[$previewRootId, '', 0]];

		while ($foldersToVisit !== []) {
			[$folderId, $folderName, $depth] = array_pop($foldersToVisit);

			if ($depth === 1 && !$this->belongsToPartition($folderName, $partition)) {
				continue;
			}

			$previewEntries = [];
			foreach ($cache->getFolderContentsById($folderId) as $entry) {
				if ($entry->getMimeType() === FileInfo::MIMETYPE_FOLDER) {
					$foldersToVisit[] = [$entry->getId(), $entry->getName(), $depth + 1];
				} else {
					$previewEntries[] = $entry;
				}
			}

			if ($previewEntries === [] || !ctype_digit($folderName)) {
				continue;
			}

			try {
				$this->migrationService->migrateFileId((int)$folderName, flatPath: $depth === 1, entries: $previewEntries);
			} catch (\Exception $e) {
				$this->logger->error('Failed to migrate preview with fileId: ' . $folderName, [
					'exception' => $e,
				]);
			}

			if (time() - $startTime > 3600) {
				return false;
			}
		}

		return true;
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
