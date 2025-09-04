<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\BackgroundJob;

use OC\Files\SetupManager;
use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Helper;
use OCA\Files_Trashbin\Trashbin;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class ExpireTrash extends TimedJob {
	public function __construct(
		private IAppConfig $appConfig,
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
		private Expiration $expiration,
		private LoggerInterface $logger,
		private SetupManager $setupManager,
		ITimeFactory $time,
	) {
		parent::__construct($time);
		// Run once per 30 minutes
		$this->setInterval(60 * 30);
	}

	protected function run($argument): void {
		$backgroundJob = $this->appConfig->getValueString('files_trashbin', 'background_job_expire_trash', 'yes');
		if ($backgroundJob === 'no') {
			return;
		}

		$maxAge = $this->expiration->getMaxAgeAsTimestamp();
		if (!$maxAge) {
			return;
		}

		$stopTime = time() + 60 * 30; // Stops after 30 minutes.
		$offset = $this->appConfig->getValueInt('files_trashbin', 'background_job_expire_trash_offset', 0);
		$users = $this->userManager->getSeenUsers($offset);

		foreach ($users as $user) {
			try {
				$uid = $user->getUID();
				$trashRoot = $this->getTrashRoot($user);
				if (!$trashRoot) {
					continue;
				}
				$dirContent = Helper::getTrashFiles('/', $uid, 'mtime');
				Trashbin::deleteExpiredFiles($dirContent, $uid);
			} catch (\Throwable $e) {
				$this->logger->error('Error while expiring trashbin for user ' . $user->getUID(), ['exception' => $e]);
			}

			$offset++;

			if ($stopTime < time()) {
				$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', $offset);
				$this->setupManager->tearDown();
				return;
			}
		}

		$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', 0);

		$this->setupManager->tearDown();
	}

	protected function getTrashRoot(IUser $user): ?Folder {
		$this->setupManager->tearDown();
		$this->setupManager->setupForUser($user);

		try {
			/** @var Folder $folder */
			$folder = $this->rootFolder->getUserFolder($user->getUID())->getParent()->get('files_trashbin');
			return $folder;
		} catch (NotFoundException|NotPermittedException) {
			$this->setupManager->tearDown();
			return null;
		}
	}
}
