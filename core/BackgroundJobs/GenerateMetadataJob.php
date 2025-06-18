<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OC\Files\Mount\MoveableMount;
use OC\FilesMetadata\Job\UpdateSingleMetadata;
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

		// This prevent the job from piling up UpdateSingleMetadata jobs
		$pendingUpdateSingleMetadataJobs = $this->jobList->countByClass(UpdateSingleMetadata::class);
		if (isset($pendingUpdateSingleMetadataJobs[0]) && $pendingUpdateSingleMetadataJobs[0]['count'] > 1000) {
			$this->logger->debug('Skipping metadata generation job as there are more than 1000 pending UpdateSingleMetadata jobs.');
			return;
		}

		$offset = $this->appConfig->getValueInt('core', 'metadataGenerationOffset', 0);
		$users = $this->userManager->getSeenUsers($offset);

		$startTime = time();

		foreach ($users as $user) {
			$this->appConfig->getValueInt('core', 'metadataGenerationOffset', ++$offset);

			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$this->scanFolder($userFolder);

			// Stop if execution time is more than one hour.
			if (time() - $startTime > 3600) {
				return;
			}
		}

		$this->appConfig->deleteKey('core', 'metadataGenerationOffset');
		$this->appConfig->setValueBool('core', 'metadataGenerationDone', true);
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
			if ($nodeSize > $nodeLimit * 1000000) {
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
