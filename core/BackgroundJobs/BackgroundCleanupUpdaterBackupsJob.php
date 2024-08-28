<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\BackgroundJobs;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
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
		$updateDir = $this->config->getSystemValue('updatedirectory', null) ?? $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');
		$instanceId = $this->config->getSystemValue('instanceid', null);

		if (!is_string($instanceId) || empty($instanceId)) {
			return;
		}

		$updaterFolderPath = $updateDir . '/updater-' . $instanceId;
		$backupFolderPath = $updaterFolderPath . '/backups';
		if (file_exists($backupFolderPath)) {
			$this->log->info("$backupFolderPath exists - start to clean it up");

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
					continue;
				}

				$dirList[$mtime] = $realPath;
			}

			ksort($dirList);
			// drop the newest 3 directories
			$dirList = array_slice($dirList, 0, -3);
			$this->log->info('List of all directories that will be deleted: ' . json_encode($dirList));

			foreach ($dirList as $dir) {
				$this->log->info("Removing $dir ...");
				\OC_Helper::rmdirr($dir);
			}
			$this->log->info('Cleanup finished');
		} else {
			$this->log->info("Could not find updater directory $backupFolderPath - cleanup step not needed");
		}
	}
}
