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
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;

class ExpireVersions extends TimedJob {
	public const ITEMS_PER_SESSION = 1000;

	private IConfig $config;
	private Expiration $expiration;
	private IUserManager $userManager;

	public function __construct(IConfig $config, IUserManager $userManager, Expiration $expiration, ITimeFactory $time) {
		parent::__construct($time);
		// Run once per 30 minutes
		$this->setInterval(60 * 30);

		$this->config = $config;
		$this->expiration = $expiration;
		$this->userManager = $userManager;
	}

	public function run($argument) {
		$backgroundJob = $this->config->getAppValue('files_versions', 'background_job_expire_versions', 'yes');
		if ($backgroundJob === 'no') {
			return;
		}

		$maxAge = $this->expiration->getMaxAgeAsTimestamp();
		if (!$maxAge) {
			return;
		}

		$this->userManager->callForSeenUsers(function (IUser $user) {
			$uid = $user->getUID();
			if (!$this->setupFS($uid)) {
				return;
			}
			Storage::expireOlderThanMaxForUser($uid);
		});
	}

	/**
	 * Act on behalf on trash item owner
	 */
	protected function setupFS(string $user): bool {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($user);

		// Check if this user has a versions directory
		$view = new \OC\Files\View('/' . $user);
		if (!$view->is_dir('/files_versions')) {
			return false;
		}

		return true;
	}
}
