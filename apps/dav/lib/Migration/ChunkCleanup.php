<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCA\DAV\BackgroundJob\UploadCleanup;
use OCP\BackgroundJob\IJobList;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ChunkCleanup implements IRepairStep {

	/** @var IConfig */
	private $config;
	/** @var IUserManager */
	private $userManager;
	/** @var IRootFolder */
	private $rootFolder;
	/** @var IJobList */
	private $jobList;

	public function __construct(IConfig $config,
		IUserManager $userManager,
		IRootFolder $rootFolder,
		IJobList $jobList) {
		$this->config = $config;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->jobList = $jobList;
	}

	public function getName(): string {
		return 'Chunk cleanup scheduler';
	}

	public function run(IOutput $output) {
		// If we already ran this onec there is no need to run it again
		if ($this->config->getAppValue('dav', 'chunks_migrated', '0') === '1') {
			$output->info('Cleanup not required');
		}

		$output->startProgress();
		// Loop over all seen users
		$this->userManager->callForSeenUsers(function (IUser $user) use ($output) {
			try {
				$userFolder = $this->rootFolder->getUserFolder($user->getUID());
				$userRoot = $userFolder->getParent();
				/** @var Folder $uploadFolder */
				$uploadFolder = $userRoot->get('uploads');
			} catch (NotFoundException $e) {
				// No folder so skipping
				return;
			}

			// Insert a cleanup job for each folder we find
			$uploads = $uploadFolder->getDirectoryListing();
			foreach ($uploads as $upload) {
				$this->jobList->add(UploadCleanup::class, ['uid' => $user->getUID(), 'folder' => $upload->getName()]);
			}
			$output->advance();
		});
		$output->finishProgress();


		$this->config->setAppValue('dav', 'chunks_migrated', '1');
	}
}
