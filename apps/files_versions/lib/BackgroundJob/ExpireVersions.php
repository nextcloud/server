<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Versions\BackgroundJob;

use OCA\Files_Versions\Expiration;
use OCA\Files_Versions\Storage;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;

class ExpireVersions extends TimedJob {
	public function __construct(
		private readonly IConfig $config,
		private readonly IUserManager $userManager,
		private readonly Expiration $expiration,
		ITimeFactory $time,
		private readonly ISetupManager $setupManager,
		private readonly IRootFolder $rootFolder,
	) {
		parent::__construct($time);
		// Run once per 30 minutes
		$this->setInterval(60 * 30);
	}

	#[\Override]
	public function run($argument) {
		$backgroundJob = $this->config->getAppValue('files_versions', 'background_job_expire_versions', 'yes');
		if ($backgroundJob === 'no') {
			return;
		}

		$maxAge = $this->expiration->getMaxAgeAsTimestamp();
		if (!$maxAge) {
			return;
		}

		$this->userManager->callForSeenUsers(function (IUser $user): void {
			$uid = $user->getUID();
			if (!$this->setupFS($user)) {
				return;
			}
			Storage::expireOlderThanMaxForUser($uid);
		});
	}

	/**
	 * Act on behalf on trash item owner
	 */
	protected function setupFS(IUser $user): bool {
		$this->setupManager->tearDown();
		$this->setupManager->setupForUser($user);

		// Check if this user has a version directory
		try {
			$this->rootFolder->get('/' . $user->getUID() . '/files_versions');
			return true;
		} catch (NotFoundException) {
			return false;
		}
	}
}
