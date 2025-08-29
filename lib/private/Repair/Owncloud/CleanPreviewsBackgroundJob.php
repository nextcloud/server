<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\Owncloud;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class CleanPreviewsBackgroundJob extends QueuedJob {
	public function __construct(
		private IRootFolder $rootFolder,
		private LoggerInterface $logger,
		private IJobList $jobList,
		ITimeFactory $timeFactory,
		private IUserManager $userManager,
	) {
		parent::__construct($timeFactory);
	}

	public function run($argument): void {
		$uid = $argument['uid'];
		if (!$this->userManager->userExists($uid)) {
			$this->logger->info('User no longer exists, skip user ' . $uid);
			return;
		}
		$this->logger->info('Started preview cleanup for ' . $uid);
		$empty = $this->cleanupPreviews($uid);

		if (!$empty) {
			$this->jobList->add(self::class, ['uid' => $uid]);
			$this->logger->info('New preview cleanup scheduled for ' . $uid);
		} else {
			$this->logger->info('Preview cleanup done for ' . $uid);
		}
	}

	/**
	 * @param string $uid
	 */
	private function cleanupPreviews($uid): bool {
		try {
			$userFolder = $this->rootFolder->getUserFolder($uid);
		} catch (NotFoundException $e) {
			return true;
		}

		$userRoot = $userFolder->getParent();

		try {
			/** @var Folder $thumbnailFolder */
			$thumbnailFolder = $userRoot->get('thumbnails');
		} catch (NotFoundException $e) {
			return true;
		}

		$thumbnails = $thumbnailFolder->getDirectoryListing();

		$start = $this->time->getTime();
		foreach ($thumbnails as $thumbnail) {
			try {
				$thumbnail->delete();
			} catch (NotPermittedException $e) {
				// Ignore
			}

			if (($this->time->getTime() - $start) > 15) {
				return false;
			}
		}

		try {
			$thumbnailFolder->delete();
		} catch (NotPermittedException $e) {
			// Ignore
		}

		return true;
	}
}
