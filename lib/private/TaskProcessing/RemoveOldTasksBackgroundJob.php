<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\TaskProcessing;

use OC\TaskProcessing\Db\TaskMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFolder;
use Psr\Log\LoggerInterface;

class RemoveOldTasksBackgroundJob extends TimedJob {
	public const MAX_TASK_AGE_SECONDS = 60 * 60 * 24 * 7 * 4; // 4 weeks
	private \OCP\Files\IAppData $appData;

	public function __construct(
		ITimeFactory $timeFactory,
		private TaskMapper $taskMapper,
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
		try {
			$this->clearFilesOlderThan($this->appData->getFolder('TaskProcessing'), self::MAX_TASK_AGE_SECONDS);
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

}
