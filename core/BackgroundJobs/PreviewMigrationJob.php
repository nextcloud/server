<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OC\Preview\Db\Preview;
use OC\Preview\PreviewMigrationService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IConfig;
use Override;
use Psr\Log\LoggerInterface;

class PreviewMigrationJob extends TimedJob {
	private string $previewRootPath;

	public function __construct(
		ITimeFactory $time,
		private readonly IAppConfig $appConfig,
		private readonly IConfig $config,
		private readonly IRootFolder $rootFolder,
		private readonly PreviewMigrationService $migrationService,
		private readonly LoggerInterface $logger,
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

		$storage = $this->rootFolder->getMountPoint()->getStorage();
		if ($storage === null) {
			$this->appConfig->setValueBool('core', 'previewMovedDone', true);
			return;
		}

		$cache = $storage->getCache();
		$previewRootId = $cache->getId(rtrim($this->previewRootPath, '/'));
		if ($previewRootId === -1) {
			// No previews have ever been generated on this instance.
			$this->appConfig->setValueBool('core', 'previewMovedDone', true);
			return;
		}

		$startTime = time();

		// Walk the preview folder tree via the `parent` column, which is indexed on
		// every supported database platform.
		//
		// Depth from the preview root tells us which structure a leaf folder holds:
		// - depth 1: legacy flat structure, e.g. preview/<fileid>/<size>.png
		// - depth 8: hierarchical structure, e.g. preview/a/b/c/d/e/f/g/<fileid>/<size>.png
		$foldersToVisit = [[$previewRootId, '', 0]];

		while ($foldersToVisit !== []) {
			[$folderId, $folderName, $depth] = array_pop($foldersToVisit);

			$hasPreviewFiles = false;
			foreach ($cache->getFolderContentsById($folderId) as $entry) {
				if ($entry->getMimeType() === FileInfo::MIMETYPE_FOLDER) {
					$foldersToVisit[] = [$entry->getId(), $entry->getName(), $depth + 1];
				} else {
					$hasPreviewFiles = true;
				}
			}

			if (!$hasPreviewFiles || !ctype_digit($folderName)) {
				continue;
			}

			try {
				$this->migrationService->migrateFileId((int)$folderName, flatPath: $depth === 1);
			} catch (\Exception $e) {
				$this->logger->error('Failed to migrate preview with fileId: ' . $folderName, [
					'exception' => $e,
				]);
			}

			// Stop if execution time is more than one hour.
			if (time() - $startTime > 3600) {
				return;
			}
		}

		$this->appConfig->setValueBool('core', 'previewMovedDone', true);
	}
}
