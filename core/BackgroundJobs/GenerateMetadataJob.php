<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OC\Files\Mount\MoveableMount;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class GenerateMetadataJob extends TimedJob {
	// Default file size limit for metadata generation (MBytes).
	protected const DEFAULT_MAX_FILESIZE = 256;

	public function __construct(
		ITimeFactory $time,
		private IConfig $config,
		private IAppConfig $appConfig,
		private IRootFolder $rootFolder,
		private IUserManager $userManager,
		private IFilesMetadataManager $filesMetadataManager,
		private IJobList $jobList,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);

		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->setInterval(24 * 60 * 60);
	}

	protected function run(mixed $argument): void {
		if ($this->appConfig->getValueBool('core', 'metadataGenerationDone', false)) {
			return;
		}

		$lastHandledUser = $this->appConfig->getValueString('core', 'metadataGenerationLastHandledUser', '');

		$users = $this->userManager->search('');

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

			$this->appConfig->setValueString('core', 'metadataGenerationLastHandledUser', $userId);
			$this->scanFilesForUser($user->getUID());

			// Stop if execution time is more than one hour.
			if (time() - $startTime > 3600) {
				return;
			}
		}

		$this->appConfig->deleteKey('core', 'metadataGenerationLastHandledUser');
		$this->appConfig->setValueBool('core', 'metadataGenerationDone', true);
	}

	private function scanFilesForUser(string $userId): void {
		$userFolder = $this->rootFolder->getUserFolder($userId);
		$this->scanFolder($userFolder);
	}

	private function scanFolder(Folder $folder): void {
		// Do not scan share and other moveable mounts.
		if ($folder->getMountPoint() instanceof MoveableMount) {
			return;
		}

		foreach ($folder->getDirectoryListing() as $node) {
			if ($node instanceof Folder) {
				$this->scanFolder($node);
				continue;
			}

			// Don't generate metadata for files bigger than configured metadata_max_filesize
			// Files are loaded in memory so very big files can lead to an OOM on the server
			$nodeSize = $node->getSize();
			$nodeLimit = $this->config->getSystemValueInt('metadata_max_filesize', self::DEFAULT_MAX_FILESIZE);
			$nodeLimitMib = $nodeLimit * 1024 * 1024;
			if ($nodeSize > $nodeLimitMib) {
				$this->logger->debug('Skipping generating metadata for fileid ' . $node->getId() . " as its size exceeds configured 'metadata_max_filesize'.");
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
					$this->logger->warning('Error while generating metadata for fileid ' . $node->getId(), ['exception' => $ex]);
				}
			}
		}
	}
}
