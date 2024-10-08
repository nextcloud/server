<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\BackgroundJob;

use OC\Lock\DBLockingProvider;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

/**
 * Clean up all file locks that are expired for the DB file locking provider
 */
class CleanupFileLocks extends TimedJob {
	/**
	 * sets the correct interval for this timed job
	 */
	public function __construct(ITimeFactory $time) {
		parent::__construct($time);
		$this->setInterval(5 * 60);
	}

	/**
	 * Makes the background job do its work
	 *
	 * @param array $argument unused argument
	 * @throws \Exception
	 */
	public function run($argument) {
		$lockingProvider = \OC::$server->getLockingProvider();
		if ($lockingProvider instanceof DBLockingProvider) {
			$lockingProvider->cleanExpiredLocks();
		}
	}
}
