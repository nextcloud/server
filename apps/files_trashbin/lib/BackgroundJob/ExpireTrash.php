<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\BackgroundJob;

use OC\Files\SetupManager;
use OC\Files\View;
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
		$backgroundJob = $this->appConfig->getValueString('files_trashbin', 'background_job_expire_trash', 'yes');
		if ($backgroundJob === 'no') {
			return;
		}

		$maxAge = $this->expiration->getMaxAgeAsTimestamp();
		if (!$maxAge) {
			return;
		}

		$stopTime = time() + self::THIRTY_MINUTES;

		do {
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

		} while (time() < $stopTime && $count === self::USER_BATCH_SIZE);

		if ($count < self::USER_BATCH_SIZE) {
			$this->resetOffset();
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

			$offset = $this->appConfig->getValueInt('files_trashbin', 'background_job_expire_trash_offset', 0);
			$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', $offset + self::USER_BATCH_SIZE);

			return $offset;
		});

	}

	private function resetOffset() {
		$this->runMutexOperation(function () {
			$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', 0);
		});
	}

	private function runMutexOperation($operation): mixed {
		$acquired = false;

		while ($acquired === false) {
			try {
				$this->lockingProvider->acquireLock('background_job_expire_trash', ILockingProvider::LOCK_EXCLUSIVE, 'Expire trashbin background job');
				$acquired = true;
			} catch (\OCP\Lock\LockedException $e) {
				// wait a bit and try again
				usleep(100000);
			}
		}

		$result = $operation();

		$this->lockingProvider->releaseLock('background_job_expire_trash', ILockingProvider::LOCK_EXCLUSIVE);

		return $result;
	}
}
