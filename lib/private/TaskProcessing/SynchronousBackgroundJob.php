<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\TaskProcessing;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\TaskProcessing\Exception\Exception;
use OCP\TaskProcessing\Exception\NotFoundException;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\ISynchronousProvider;
use OCP\TaskProcessing\Task;
use Psr\Log\LoggerInterface;

class SynchronousBackgroundJob extends QueuedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private readonly IManager $taskProcessingManager,
		private readonly IJobList $jobList,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($timeFactory);
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	protected function run($argument) {
		$providers = $this->taskProcessingManager->getProviders();

		foreach ($providers as $provider) {
			if (!$provider instanceof ISynchronousProvider) {
				continue;
			}
			$taskTypeId = $provider->getTaskTypeId();
			// only use this provider if it is the preferred one
			$preferredProvider = $this->taskProcessingManager->getPreferredProvider($taskTypeId);
			if ($provider->getId() !== $preferredProvider->getId()) {
				continue;
			}
			try {
				// Atomically claim the oldest scheduled task and mark it RUNNING in one step.
				// Without this, a concurrently running taskprocessing:worker could pick up the
				// same row: this background job used to fetch-then-process, and processTask's
				// setTaskStatus(RUNNING) would blindly overwrite, so both executors ran the same
				// task. The atomic claim (FOR UPDATE SKIP LOCKED, with a SQLite/Oracle fallback)
				// guarantees at most one executor ever transitions a task SCHEDULED -> RUNNING.
				$task = $this->taskProcessingManager->claimNextScheduledTask([$taskTypeId]);
			} catch (Exception $e) {
				$this->logger->error('Unknown error while claiming scheduled TaskProcessing tasks', ['exception' => $e]);
				continue;
			}
			if ($task === null) {
				// No schedulable task for this task type right now.
				continue;
			}
			if (!$this->taskProcessingManager->processTask($task, $provider)) {
				// Schedule again
				$this->jobList->add(self::class, $argument);
			}
		}

		// check if this job needs to be scheduled again:
		// if there is at least one preferred synchronous provider that has a scheduled task
		$synchronousProviders = array_filter($providers, fn ($provider)
			=> $provider instanceof ISynchronousProvider);
		$synchronousPreferredProviders = array_filter($synchronousProviders, function ($provider) {
			$taskTypeId = $provider->getTaskTypeId();
			$preferredProvider = $this->taskProcessingManager->getPreferredProvider($taskTypeId);
			return $provider->getId() === $preferredProvider->getId();
		});
		$taskTypes = array_values(
			array_map(
				fn ($provider) => $provider->getTaskTypeId(),
				$synchronousPreferredProviders
			)
		);
		$taskTypesWithTasks = array_filter($taskTypes, function ($taskType) {
			try {
				$this->taskProcessingManager->getNextScheduledTask([$taskType]);
				return true;
			} catch (NotFoundException|Exception $e) {
				return false;
			}
		});

		if (count($taskTypesWithTasks) > 0) {
			// Schedule again
			$this->jobList->add(self::class, $argument);
		}
	}
}
