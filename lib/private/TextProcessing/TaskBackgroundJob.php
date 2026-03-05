<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OC\TextProcessing;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\TextProcessing\Events\TaskFailedEvent;
use OCP\TextProcessing\Events\TaskSuccessfulEvent;
use OCP\TextProcessing\IManager;

class TaskBackgroundJob extends QueuedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private IManager $textProcessingManager,
		private IEventDispatcher $eventDispatcher,
	) {
		parent::__construct($timeFactory);
		// We want to avoid overloading the machine with these jobs
		// so we only allow running one job at a time
		$this->setAllowParallelRuns(false);
	}

	/**
	 * @param array{taskId: int} $argument
	 * @inheritDoc
	 */
	protected function run($argument) {
		$taskId = $argument['taskId'];
		$task = $this->textProcessingManager->getTask($taskId);
		try {
			$this->textProcessingManager->runTask($task);
			$event = new TaskSuccessfulEvent($task);
		} catch (\Throwable $e) {
			$event = new TaskFailedEvent($task, $e->getMessage());
		}
		$this->eventDispatcher->dispatchTyped($event);
	}
}
