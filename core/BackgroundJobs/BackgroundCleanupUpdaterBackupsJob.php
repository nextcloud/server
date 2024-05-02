<?php

declare(strict_types=1);

/**
 * @copyright 2018 Morris Jobke <hey@morrisjobke.de>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
			$this->log->info("List of all directories that will be deleted: " . json_encode($dirList));

			foreach ($dirList as $dir) {
				$this->log->info("Removing $dir ...");
				\OC_Helper::rmdirr($dir);
			}
			$this->log->info("Cleanup finished");
		} else {
			$this->log->info("Could not find updater directory $backupFolderPath - cleanup step not needed");
		}
	}
}
