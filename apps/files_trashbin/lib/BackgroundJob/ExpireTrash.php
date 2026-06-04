<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\BackgroundJob;

use OCA\Files_Trashbin\AppInfo\Application;
use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Trashbin;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use Override;
use Psr\Log\LoggerInterface;

class ExpireTrash extends TimedJob {
	public const TOGGLE_CONFIG_KEY_NAME = 'background_job_expire_trash';
	public const OFFSET_CONFIG_KEY_NAME = 'background_job_expire_trash_offset';
	private const THIRTY_MINUTES = 30 * 60;
	private const USER_BATCH_SIZE = 10;

	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IUserManager $userManager,
		private readonly Expiration $expiration,
		private readonly LoggerInterface $logger,
		private readonly ISetupManager $setupManager,
		private readonly ILockingProvider $lockingProvider,
		private readonly IRootFolder $rootFolder,
		ITimeFactory $time,
	) {
		parent::__construct($time);
		$this->setInterval(self::THIRTY_MINUTES);
	}

	#[Override]
	protected function run($argument): void {
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
					$folder = $this->getTrashRoot($user);
					Trashbin::expire($folder, $user);
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

	private function getTrashRoot(IUser $user): Folder {
		$this->setupManager->tearDown();
		$this->setupManager->setupForUser($user);

		$folder = $this->rootFolder->getUserFolder($user->getUID())->getParent()->get('files_trashbin');
		if (!$folder instanceof Folder) {
			throw new \LogicException("Didn't expect files_trashbin to be a file instead of a folder");
		}
		return $folder;
	}

	private function getNextOffset(): int {
		return $this->runMutexOperation(function (): int {
			$this->appConfig->clearCache();

			$offset = $this->appConfig->getValueInt(Application::APP_ID, self::OFFSET_CONFIG_KEY_NAME, 0);
			$this->appConfig->setValueInt(Application::APP_ID, self::OFFSET_CONFIG_KEY_NAME, $offset + self::USER_BATCH_SIZE);

			return $offset;
		});

	}

	private function resetOffset(): void {
		$this->runMutexOperation(function (): void {
			$this->appConfig->setValueInt(Application::APP_ID, self::OFFSET_CONFIG_KEY_NAME, 0);
		});
	}

	/**
	 * @template T
	 * @param callable(): T $operation
	 * @return T
	 */
	private function runMutexOperation(callable $operation): mixed {
		$acquired = false;

		while ($acquired === false) {
			try {
				$this->lockingProvider->acquireLock(self::OFFSET_CONFIG_KEY_NAME, ILockingProvider::LOCK_EXCLUSIVE, 'Expire trashbin background job offset');
				$acquired = true;
			} catch (LockedException $e) {
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
