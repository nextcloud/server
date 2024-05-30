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
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;

class CheckForUserCertificates extends QueuedJob {
	public function __construct(
		protected IConfig $config,
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
		ITimeFactory $time,
	) {
		parent::__construct($time);
	}

	/**
	 * Checks all user directories for old user uploaded certificates
	 */
	public function run($arguments): void {
		$uploadList = [];
		$this->userManager->callForSeenUsers(function (IUser $user) use (&$uploadList) {
			$userId = $user->getUID();
			try {
				\OC_Util::setupFS($userId);
				$filesExternalUploadsFolder = $this->rootFolder->get($userId . '/files_external/uploads');
			} catch (NotFoundException $e) {
				\OC_Util::tearDownFS();
				return;
			}
			if ($filesExternalUploadsFolder instanceof Folder) {
				$files = $filesExternalUploadsFolder->getDirectoryListing();
				foreach ($files as $file) {
					$filename = $file->getName();
					$uploadList[] = "$userId/files_external/uploads/$filename";
				}
			}
			\OC_Util::tearDownFS();
		});

		if (empty($uploadList)) {
			$this->config->deleteAppValue('files_external', 'user_certificate_scan');
		} else {
			$this->config->setAppValue('files_external', 'user_certificate_scan', json_encode($uploadList));
		}
	}
}
