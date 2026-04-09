<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\FilesMetadata\Job;

use OC\FilesMetadata\FilesMetadataManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\IRootFolder;
use OCP\FilesMetadata\Event\MetadataLiveEvent;
use OCP\FilesMetadata\IFilesMetadataManager;
use Psr\Log\LoggerInterface;

/**
 * Simple background job, created when requested by an app during the
 * dispatch of MetadataLiveEvent.
 * This background job will re-run the event to refresh metadata on a non-live thread.
 *
 * @see MetadataLiveEvent::requestBackgroundJob()
 * @since 28.0.0
 */
class UpdateSingleMetadata extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private IRootFolder $rootFolder,
		private FilesMetadataManager $filesMetadataManager,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
	}

	protected function run($argument) {
		[$userId, $fileId] = $argument;

		try {
			$node = $this->rootFolder->getUserFolder($userId)->getFirstNodeById($fileId);
			if ($node) {
				$this->filesMetadataManager->refreshMetadata($node, IFilesMetadataManager::PROCESS_BACKGROUND);
			}
		} catch (\Exception $e) {
			$this->logger->warning('issue while running UpdateSingleMetadata', ['exception' => $e, 'userId' => $userId, 'fileId' => $fileId]);
		}
	}
}
