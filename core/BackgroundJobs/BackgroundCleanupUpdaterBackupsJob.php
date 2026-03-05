<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\BackgroundJobs;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class BackgroundCleanupUpdaterBackupsJob extends QueuedJob {
	public function __construct(
		protected IConfig $config,
		protected LoggerInterface $log,
		ITimeFactory $time,
	) {
		parent::__construct($time);
	}

	/**
	 * This job cleans up all backups except the latest 3 from the updaters backup directory
	 *
	 * @param array $argument
	 */
	public function run($argument): void {
		$this->log->info('Running background job to clean-up outdated updater backups');

		$updateDir = $this->config->getSystemValue('updatedirectory', null) ?? $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');
		$instanceId = $this->config->getSystemValue('instanceid', null);

		if (!is_string($instanceId) || empty($instanceId)) {
			$this->log->error('Skipping updater backup clean-up - instanceId is missing!');
			return;
		}

		$updaterFolderPath = $updateDir . '/updater-' . $instanceId;
		$backupFolderPath = $updaterFolderPath . '/backups';
		if (file_exists($backupFolderPath)) {
			$this->log->debug("Updater backup folder detected: $backupFolderPath");

			$dirList = [];
			$dirs = new \DirectoryIterator($backupFolderPath);
			foreach ($dirs as $dir) {
				// skip files and dot dirs
				if ($dir->isFile() || $dir->isDot()) {
					continue;
				}

				$mtime = $dir->getMTime();
				$realPath = $dir->getRealPath();

				if ($realPath === false) {
					$pathName = $dir->getPathname();
					$this->log->warning("Skipping updater backup folder: $pathName (not found)");
					continue;
				}

				$dirList[$mtime] = $realPath;
			}

			ksort($dirList);
			// drop the newest 3 directories
			$dirList = array_slice($dirList, 0, -3);
			$this->log->debug('Updater backup folders that will be deleted: ' . json_encode($dirList));

			foreach ($dirList as $dir) {
				$this->log->info("Removing $dir ...");
				$result = Files::rmdirr($dir);
				if (!$result) {
					$this->log->error('Could not remove updater backup folder $dir');
				}
			}
			$this->log->info('Background job to clean-up updater backups has finished');
		} else {
			$this->log->warning("Skipping updater backup clean-up - could not find updater backup folder $backupFolderPath");
		}
	}
}
