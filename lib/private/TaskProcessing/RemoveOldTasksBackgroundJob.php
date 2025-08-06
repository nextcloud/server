<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\TaskProcessing;

use OC\TaskProcessing\Db\TaskMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\File;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\TaskProcessing\IManager;
use Psr\Log\LoggerInterface;

class RemoveOldTasksBackgroundJob extends TimedJob {
	public const MAX_TASK_AGE_SECONDS = 60 * 60 * 24 * 30 * 4; // 4 months
	private \OCP\Files\IAppData $appData;

	public function __construct(
		ITimeFactory $timeFactory,
		private TaskMapper $taskMapper,
		private IManager $taskProcessingManager,
		private IRootFolder $rootFolder,
		private LoggerInterface $logger,
		IAppDataFactory $appDataFactory,
	) {
		parent::__construct($timeFactory);
		$this->setInterval(60 * 60 * 24);
		// can be deferred to maintenance window
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->appData = $appDataFactory->get('core');
	}


	/**
	 * @inheritDoc
	 */
	protected function run($argument): void {
		try {
			$this->cleanupTaskProcessingTaskFiles();
		} catch (\Exception $e) {
			$this->logger->warning('Failed to delete stale task processing tasks files', ['exception' => $e]);
		}
		try {
			$this->taskMapper->deleteOlderThan(self::MAX_TASK_AGE_SECONDS);
		} catch (\OCP\DB\Exception $e) {
			$this->logger->warning('Failed to delete stale task processing tasks', ['exception' => $e]);
		}
		try {
			$this->clearFilesOlderThan($this->appData->getFolder('text2image'), self::MAX_TASK_AGE_SECONDS);
		} catch (NotFoundException $e) {
			// noop
		}
		try {
			$this->clearFilesOlderThan($this->appData->getFolder('audio2text'), self::MAX_TASK_AGE_SECONDS);
		} catch (NotFoundException $e) {
			// noop
		}
	}

	/**
	 * @param ISimpleFolder $folder
	 * @param int $ageInSeconds
	 * @return void
	 */
	private function clearFilesOlderThan(ISimpleFolder $folder, int $ageInSeconds): void {
		foreach ($folder->getDirectoryListing() as $file) {
			if ($file->getMTime() < time() - $ageInSeconds) {
				try {
					$file->delete();
				} catch (NotPermittedException $e) {
					$this->logger->warning('Failed to delete a stale task processing file', ['exception' => $e]);
				}
			}
		}
	}

	/**
	 * @return void
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws \JsonException
	 * @throws Exception
	 * @throws \OCP\TaskProcessing\Exception\NotFoundException
	 */
	private function cleanupTaskProcessingTaskFiles(): void {
		foreach ($this->taskMapper->getTasksToCleanup(self::MAX_TASK_AGE_SECONDS) as $task) {
			$ocpTask = $task->toPublicTask();
			$fileIds = $this->taskProcessingManager->extractFileIdsFromTask($ocpTask);
			foreach ($fileIds as $fileId) {
				// only look for output files stored in appData/TaskProcessing/
				$file = $this->rootFolder->getFirstNodeByIdInPath($fileId, '/' . $this->rootFolder->getAppDataDirectoryName() . '/TaskProcessing/');
				if ($file instanceof File) {
					try {
						$file->delete();
					} catch (NotPermittedException $e) {
						$this->logger->warning('Failed to delete a stale task processing file', ['exception' => $e]);
					}
				}
			}
		}
	}
}
