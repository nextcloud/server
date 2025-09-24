<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\BackgroundJob;

use OC\Files\SetupManager;
use OC\Files\View;
use OCA\Files_Trashbin\AppInfo\Application;
use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Helper;
use OCA\Files_Trashbin\Trashbin;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Lock\ILockingProvider;
use Psr\Log\LoggerInterface;

class ExpireTrash extends TimedJob {
	public const TOGGLE_CONFIG_KEY_NAME = 'background_job_expire_trash';
	public const OFFSET_CONFIG_KEY_NAME = 'background_job_expire_trash_offset';
	private const THIRTY_MINUTES = 30 * 60;
	private const USER_BATCH_SIZE = 10;

	public function __construct(
		private IAppConfig $appConfig,
		private IUserManager $userManager,
		private Expiration $expiration,
		private LoggerInterface $logger,
		private SetupManager $setupManager,
		private ILockingProvider $lockingProvider,
		ITimeFactory $time,
	) {
		parent::__construct($time);
		$this->setInterval(self::THIRTY_MINUTES);
	}

	protected function run($argument) {
		$backgroundJob = $this->appConfig->getValueBool(Application::APP_ID, self::TOGGLE_CONFIG_KEY_NAME, true);
		if (!$backgroundJob) {
			return;
		}

		$maxAge = $this->expiration->getMaxAgeAsTimestamp();
		if (!$maxAge) {
			return;
		}

		$startTime = time();

		// Process users in batches of 10, but don't run for more than 30 minutes
		while (time() < $startTime + self::THIRTY_MINUTES) {
			$offset = $this->getNextOffset();
			$users = $this->userManager->getSeenUsers($offset, self::USER_BATCH_SIZE);
			$count = 0;

			foreach ($users as $user) {
				$uid = $user->getUID();
				$count++;

				try {
					if ($this->setupFS($user)) {
						$dirContent = Helper::getTrashFiles('/', $uid, 'mtime');
						Trashbin::deleteExpiredFiles($dirContent, $uid);
					}
				} catch (\Throwable $e) {
					$this->logger->error('Error while expiring trashbin for user ' . $uid, ['exception' => $e]);
				} finally {
					$this->setupManager->tearDown();
				}
			}

			// If the last batch was not full it means that we reached the end of the user list.
			if ($count < self::USER_BATCH_SIZE) {
				$this->resetOffset();
				break;
			}
		}
	}

	/**
	 * Act on behalf on trash item owner
	 */
	protected function setupFS(IUser $user): bool {
		$this->setupManager->setupForUser($user);

		// Check if this user has a trashbin directory
		$view = new View('/' . $user->getUID());
		if (!$view->is_dir('/files_trashbin/files')) {
			return false;
		}

		return true;
	}

	private function getNextOffset(): int {
		return $this->runMutexOperation(function () {
			$this->appConfig->clearCache();

			$offset = $this->appConfig->getValueInt(Application::APP_ID, self::OFFSET_CONFIG_KEY_NAME, 0);
			$this->appConfig->setValueInt(Application::APP_ID, self::OFFSET_CONFIG_KEY_NAME, $offset + self::USER_BATCH_SIZE);

			return $offset;
		});

	}

	private function resetOffset() {
		$this->runMutexOperation(function () {
			$this->appConfig->setValueInt(Application::APP_ID, self::OFFSET_CONFIG_KEY_NAME, 0);
		});
	}

	private function runMutexOperation($operation): mixed {
		$acquired = false;

		while ($acquired === false) {
			try {
				$this->lockingProvider->acquireLock(self::OFFSET_CONFIG_KEY_NAME, ILockingProvider::LOCK_EXCLUSIVE, 'Expire trashbin background job offset');
				$acquired = true;
			} catch (\OCP\Lock\LockedException $e) {
				// wait a bit and try again
				usleep(100000);
			}
		}

		try {
			$result = $operation();
		} finally {
			$this->lockingProvider->releaseLock(self::OFFSET_CONFIG_KEY_NAME, ILockingProvider::LOCK_EXCLUSIVE);
		}

		return $result;
	}
}
