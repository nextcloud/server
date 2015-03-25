<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Versions;

use OCP\Files\File;

class Expirer {
	const DEFAULTMAXSIZE = 50;

	/**
	 * @var \OCA\Files_Versions\Store
	 */
	private $store;

	/**
	 * @var \OCP\IConfig
	 */
	private $config;

	private $maxVersionsPerInterval = [
		//first 10sec, one version every 2sec
		1 => ['intervalEndsAfter' => 10, 'step' => 2],
		//next minute, one version every 10sec
		2 => ['intervalEndsAfter' => 60, 'step' => 10],
		//next hour, one version every minute
		3 => ['intervalEndsAfter' => 3600, 'step' => 60],
		//next 24h, one version every hour
		4 => ['intervalEndsAfter' => 86400, 'step' => 3600],
		//next 30days, one version per day
		5 => ['intervalEndsAfter' => 2592000, 'step' => 86400],
		//until the end one version per week
		6 => ['intervalEndsAfter' => -1, 'step' => 604800],
	];

	/**
	 * @var \OCP\Files\Folder
	 */
	private $userFolder;

	/**
	 * @param int $interval
	 * @param int $time
	 * @return array [int nextInterval, int step]
	 */
	private function getNextInterval($interval, $time) {
		$step = $this->maxVersionsPerInterval[$interval]['step'];
		if ($this->maxVersionsPerInterval[$interval]['intervalEndsAfter'] == -1) {
			return [-1, $step];
		} else {
			return [$time - $this->maxVersionsPerInterval[$interval]['intervalEndsAfter'], $step];
		}
	}

	/**
	 * Filter a list of versions to get the ones we want to delete
	 *
	 * @param \OCA\Files_Versions\Version[] $allVersions
	 * @param integer $time
	 * @return \OCA\Files_Versions\Version[]
	 */
	protected function getExpireList($allVersions, $time) {

		$toDelete = [];  // versions we want to delete

		$interval = 1;
		list($nextInterval, $step) = $this->getNextInterval($interval, $time);

		/** @var \OCA\Files_Versions\Version $firstVersion */
		$firstVersion = array_shift($allVersions);
		$prevTimestamp = $firstVersion->getMtime();
		$nextVersion = $prevTimestamp - $step;

		foreach ($allVersions as $version) {
			$newInterval = true;
			while ($newInterval) {
				if ($nextInterval == -1 || $prevTimestamp > $nextInterval) {
					if ($version->getMtime() > $nextVersion) {
						//distance between two version too small, mark to delete
						$toDelete[] = $version;
					} else {
						$nextVersion = $version->getMtime() - $step;
						$prevTimestamp = $version->getMtime();
					}
					$newInterval = false; // version checked so we can move to the next one
				} else { // time to move on to the next interval
					$interval++;
					list($step, $nextInterval) = $this->getNextInterval($interval, $time);
					$newInterval = true; // we changed the interval -> check same version with new interval
				}
			}
		}

		return $toDelete;
	}

	/**
	 * Get the amount of free space we have for version
	 *
	 * @return int
	 */
	private function getAvailableSpace() {
		// get available disk space for user
		$softQuota = true;
		$quota = $this->config->getUserValue($uid, 'files', 'quota', null);
		if ($quota === null || $quota === 'default') {
			$quota = $this->config->getAppValue('files', 'default_quota', null);
		}
		if ($quota === null || $quota === 'none') {
			$quota = $this->userFolder->getFreeSpace();
			$softQuota = false;
		} else {
			$quota = \OCP\Util::computerFileSize($quota);
		}

		$versionsSize = $this->store->getSize();

		// calculate available space for version history
		// subtract size of files and current versions size from quota
		if ($softQuota) {
			$free = $quota - $this->userFolder->getSize(); // remaining free space for user
			if ($free > 0) {
				return ($free * self::DEFAULTMAXSIZE / 100) - ($versionsSize); // how much space can be used for versions
			} else {
				return $free - $versionsSize;
			}
		} else {
			return $quota;
		}
	}

	/**
	 * @param \OCP\Files\File $file
	 * @param int $time
	 * @return int the total size of all deleted versions
	 */
	public function expire(File $file, $time) {
		$availableSpace = $this->getAvailableSpace();

		$allVersions = $this->store->listVersions($file);
		$deletedSize = 0;

		$toDelete = $this->getExpireList($allVersions, $time);
		$toDeleteSize = array_reduce($toDelete, function ($size, Version $version) {
			return $size + $version->getVersionFile()->getSize();
		}, 0);

		// if still not enough free space we rearrange the versions from all files
		if (($availableSpace + $toDeleteSize) <= 0) {
			$allVersions = $this->store->listAllVersions();
			$toDelete = $this->getExpireList($allVersions, $time);
		}

		foreach ($toDelete as $version) {
			\OC_Hook::emit('\OCP\Versions', 'preDelete', array('path' => $version->getVersionFile()->getPath()));
			$deletedSize += $version->getVersionFile()->getSize();
			$this->store->remove($version);
			\OC_Hook::emit('\OCP\Versions', 'delete', array('path' => $version->getVersionFile()->getPath()));
			\OCP\Util::writeLog('files_versions', "Expire: " . $version->getVersionFile()->getPath(), \OCP\Util::DEBUG);
		}

		$availableSpace = $availableSpace + $toDeleteSize;

		// Check if enough space is available after versions are rearranged.
		// If not we delete the oldest versions until we meet the size limit for versions,
		// but always keep the two latest versions
		$numOfVersions = count($allVersions) - 2;
		$i = 0;
		// sort oldest first and make sure that we start at the first element

		usort($allVersions, function (Version $a, Version $b) {
			return $a->getMtime() < $b->getMtime();
		});

		while ($availableSpace < 0 && $i < $numOfVersions) {
			/** @var \OCA\Files_Versions\Version $version */
			$version = array_shift($allVersions);
			\OC_Hook::emit('\OCP\Versions', 'preDelete', array('path' => $version->getSourceFile()->getPath()));
			$size = $version->getSourceFile()->getSize();
			$this->store->remove($version);
			\OC_Hook::emit('\OCP\Versions', 'delete', array('path' => $version->getSourceFile()->getPath()));
			\OCP\Util::writeLog('files_versions', 'running out of space! Delete oldest version: ' . $version->getSourceFile()->getPath(), \OCP\Util::DEBUG);
			$availableSpace += $size;
			$deletedSize += $size;
			$i++;
		}
		return $deletedSize;
	}
}
