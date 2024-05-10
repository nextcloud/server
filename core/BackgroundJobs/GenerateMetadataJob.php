<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class GenerateMetadataJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private IConfig $config,
		private IRootFolder $rootFolder,
		private IUserManager $userManager,
		private IFilesMetadataManager $filesMetadataManager,
		private IJobList $jobList,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);

		$this->setTimeSensitivity(\OCP\BackgroundJob\IJob::TIME_INSENSITIVE);
		$this->setInterval(24 * 3600);
	}

	protected function run(mixed $argument): void {
		$users = $this->userManager->search('');
		$lastHandledUser = $this->config->getAppValue('core', 'metadataGenerationLastHandledUser', '');

		// we'll only start timer once we have found a valid user to handle
		// meaning NOW if we have not handled any user from a previous run
		$startTime = ($lastHandledUser === '') ? time() : null;
		foreach ($users as $user) {
			$userId = $user->getUID();

			// if we already handled a previous run, we start timer only when we face the last handled user
			if ($startTime === null) {
				if ($userId === $lastHandledUser) {
					$startTime = time();
				}
				continue;
			}

			$this->config->setAppValue('core', 'metadataGenerationLastHandledUser', $userId);
			$this->scanFilesForUser($user->getUID());

			// Stop if execution time is more than one hour.
			if (time() - $startTime > 3600) {
				return;
			}
		}

		$this->jobList->remove(GenerateMetadataJob::class);
		$this->config->deleteAppValue('core', 'metadataGenerationLastHandledUser');
	}

	private function scanFilesForUser(string $userId): void {
		$userFolder = $this->rootFolder->getUserFolder($userId);
		$this->scanFolder($userFolder);
	}

	private function scanFolder(Folder $folder): void {
		// Do not scan share and other moveable mounts.
		if ($folder->getMountPoint() instanceof \OC\Files\Mount\MoveableMount) {
			return;
		}

		foreach ($folder->getDirectoryListing() as $node) {
			if ($node instanceof Folder) {
				$this->scanFolder($node);
				continue;
			}

			try {
				$this->filesMetadataManager->getMetadata($node->getId(), false);
			} catch (FilesMetadataNotFoundException) {
				try {
					$this->filesMetadataManager->refreshMetadata(
						$node,
						IFilesMetadataManager::PROCESS_LIVE | IFilesMetadataManager::PROCESS_BACKGROUND
					);
				} catch (\Throwable $ex) {
					$this->logger->warning("Error while generating metadata for fileid " . $node->getId(), ['exception' => $ex]);
				}
			}
		}
	}
}
