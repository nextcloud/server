<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OC\TextToImage;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\TextToImage\Events\TaskFailedEvent;
use OCP\TextToImage\Events\TaskSuccessfulEvent;
use OCP\TextToImage\IManager;

class TaskBackgroundJob extends QueuedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private IManager $text2imageManager,
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
		$task = $this->text2imageManager->getTask($taskId);
		try {
			$this->text2imageManager->runTask($task);
			$event = new TaskSuccessfulEvent($task);
		} catch (\Throwable $e) {
			$event = new TaskFailedEvent($task, $e->getMessage());
		}
		$this->eventDispatcher->dispatchTyped($event);
	}
}
