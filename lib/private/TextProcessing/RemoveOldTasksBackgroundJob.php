<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OC\TextProcessing;

use OC\TextProcessing\Db\TaskMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;

class RemoveOldTasksBackgroundJob extends TimedJob {
	public const MAX_TASK_AGE_SECONDS = 60 * 60 * 24 * 7; // 1 week

	public function __construct(
		ITimeFactory $timeFactory,
		private TaskMapper $taskMapper,
		private LoggerInterface $logger,
	) {
		parent::__construct($timeFactory);
		$this->setInterval(60 * 60 * 24);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	/**
	 * @param mixed $argument
	 * @inheritDoc
	 */
	protected function run($argument) {
		try {
			$this->taskMapper->deleteOlderThan(self::MAX_TASK_AGE_SECONDS);
		} catch (Exception $e) {
			$this->logger->warning('Failed to delete stale language model tasks', ['exception' => $e]);
		}
	}
}
