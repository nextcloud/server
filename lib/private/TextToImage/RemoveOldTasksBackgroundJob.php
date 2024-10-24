<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OC\TextToImage;

use OC\TextToImage\Db\TaskMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Psr\Log\LoggerInterface;

class RemoveOldTasksBackgroundJob extends TimedJob {
	public const MAX_TASK_AGE_SECONDS = 60 * 60 * 24 * 7; // 1 week

	private IAppData $appData;

	public function __construct(
		ITimeFactory $timeFactory,
		private TaskMapper $taskMapper,
		private LoggerInterface $logger,
		IAppDataFactory $appDataFactory,
	) {
		parent::__construct($timeFactory);
		$this->appData = $appDataFactory->get('core');
		$this->setInterval(60 * 60 * 24);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	/**
	 * @param mixed $argument
	 * @inheritDoc
	 */
	protected function run($argument) {
		try {
			$deletedTasks = $this->taskMapper->deleteOlderThan(self::MAX_TASK_AGE_SECONDS);
			$folder = $this->appData->getFolder('text2image');
			foreach ($deletedTasks as $deletedTask) {
				try {
					$folder->getFolder((string)$deletedTask->getId())->delete();
				} catch (NotFoundException) {
					// noop
				} catch (NotPermittedException $e) {
					$this->logger->warning('Failed to delete stale text to image task files', ['exception' => $e]);
				}
			}
		} catch (Exception $e) {
			$this->logger->warning('Failed to delete stale text to image tasks', ['exception' => $e]);
		} catch (NotFoundException) {
			// noop
		}
	}
}
