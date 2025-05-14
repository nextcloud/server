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
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class ExpireTrash extends TimedJob {
	private const THIRTY_MINUTES = 30 * 60;

	public function __construct(
		private IAppConfig $appConfig,
		private IUserManager $userManager,
		private Expiration $expiration,
		private LoggerInterface $logger,
		private SetupManager $setupManager,
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
			$this->appConfig->clearCache();
			$offset = $this->appConfig->getValueInt('files_trashbin', 'background_job_expire_trash_offset', 0);
			$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', $offset + 10);

			$users = $this->userManager->getLastLoggedInUsers(10, $offset);

			foreach ($users as $uid) {
				try {
					if ($this->setupFS($uid)) {
						$dirContent = Helper::getTrashFiles('/', $uid, 'mtime');
						Trashbin::deleteExpiredFiles($dirContent, $uid);
					}
				} catch (\Throwable $e) {
					$this->logger->error('Error while expiring trashbin for user ' . $uid, ['exception' => $e]);
				}

				$this->setupManager->tearDown();
			}

		} while (time() < $stopTime && count($users) === 10);

		if (count($users) < 10) {
			$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', 0);
		}
	}

	/**
	 * Act on behalf on trash item owner
	 */
	protected function setupFS(string $uid): bool {
		$this->setupManager->setupForUser($this->userManager->get($uid));

		// Check if this user has a trashbin directory
		$view = new View('/' . $uid);
		if (!$view->is_dir('/files_trashbin/files')) {
			return false;
		}

		return true;
	}
}
