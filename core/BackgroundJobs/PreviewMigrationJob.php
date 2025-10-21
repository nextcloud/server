<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OC\Preview\Db\Preview;
use OC\Preview\PreviewMigrationService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\IResult;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use Override;

class PreviewMigrationJob extends TimedJob {
	private string $previewRootPath;

	public function __construct(
		ITimeFactory $time,
		private readonly IAppConfig $appConfig,
		private readonly IConfig $config,
		private readonly IDBConnection $connection,
		private readonly IRootFolder $rootFolder,
		private readonly PreviewMigrationService $migrationService,
	) {
		parent::__construct($time);

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
		while ($row = $result->fetch()) {
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
			$this->migrationService->migrateFileId($fileId, flatPath: false);
		}

		foreach ($flatFileIds as $fileId) {
			$this->migrationService->migrateFileId($fileId, flatPath: true);
		}
		return $foundPreview;
	}
}
