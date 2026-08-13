<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;

class CheckForUserCertificates extends QueuedJob {
	public function __construct(
		private readonly IConfig $config,
		private readonly IUserManager $userManager,
		private readonly IRootFolder $rootFolder,
		private readonly ISetupManager $setupManager,
		ITimeFactory $time,
	) {
		parent::__construct($time);
	}

	/**
	 * Checks all user directories for old user uploaded certificates
	 */
	#[\Override]
	public function run($argument): void {
		$uploadList = [];
		$this->userManager->callForSeenUsers(function (IUser $user) use (&$uploadList): void {
			$userId = $user->getUID();
			try {
				$this->setupManager->setupForUser($user);
				$filesExternalUploadsFolder = $this->rootFolder->get($userId . '/files_external/uploads');
			} catch (NotFoundException $e) {
				$this->setupManager->tearDown();
				return;
			}
			if ($filesExternalUploadsFolder instanceof Folder) {
				$files = $filesExternalUploadsFolder->getDirectoryListing();
				foreach ($files as $file) {
					$filename = $file->getName();
					$uploadList[] = "$userId/files_external/uploads/$filename";
				}
			}
			$this->setupManager->tearDown();
		});

		if (empty($uploadList)) {
			$this->config->deleteAppValue('files_external', 'user_certificate_scan');
		} else {
			$this->config->setAppValue('files_external', 'user_certificate_scan', json_encode($uploadList));
		}
	}
}
