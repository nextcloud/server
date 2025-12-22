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
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use Override;
use Psr\Log\LoggerInterface;

class RemoveOldTasksBackgroundJob extends TimedJob {
	private IAppData $appData;

	public function __construct(
		ITimeFactory $timeFactory,
		private Manager $taskProcessingManager,
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

	#[Override]
	protected function run($argument): void {
		try {
			iterator_to_array($this->taskProcessingManager->cleanupTaskProcessingTaskFiles());
		} catch (\Exception $e) {
			$this->logger->warning('Failed to delete stale task processing tasks files', ['exception' => $e]);
		}
		try {
			$this->taskMapper->deleteOlderThan(Manager::MAX_TASK_AGE_SECONDS);
		} catch (\OCP\DB\Exception $e) {
			$this->logger->warning('Failed to delete stale task processing tasks', ['exception' => $e]);
		}
		try {
			iterator_to_array($this->taskProcessingManager->clearFilesOlderThan($this->appData->getFolder('text2image')));
		} catch (NotFoundException) {
			// noop
		}
		try {
			iterator_to_array($this->taskProcessingManager->clearFilesOlderThan($this->appData->getFolder('audio2text')));
		} catch (NotFoundException) {
			// noop
		}
	}
}
