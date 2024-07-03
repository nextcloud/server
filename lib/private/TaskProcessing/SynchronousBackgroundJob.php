<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\TaskProcessing;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\GenericFileException;
use OCP\Files\NotPermittedException;
use OCP\Lock\LockedException;
use OCP\TaskProcessing\Exception\Exception;
use OCP\TaskProcessing\Exception\NotFoundException;
use OCP\TaskProcessing\Exception\ProcessingException;
use OCP\TaskProcessing\Exception\ValidationException;
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
	protected function run($argument) {
		$providers = $this->taskProcessingManager->getProviders();

		foreach ($providers as $provider) {
			if (!$provider instanceof ISynchronousProvider) {
				continue;
			}
			$taskType = $provider->getTaskTypeId();
			try {
				$task = $this->taskProcessingManager->getNextScheduledTask([$taskType]);
			} catch (NotFoundException $e) {
				continue;
			} catch (Exception $e) {
				$this->logger->error('Unknown error while retrieving scheduled TaskProcessing tasks', ['exception' => $e]);
				continue;
			}
			try {
				try {
					$input = $this->taskProcessingManager->prepareInputData($task);
				} catch (GenericFileException|NotPermittedException|LockedException|ValidationException $e) {
					$this->logger->warning('Failed to prepare input data for a TaskProcessing task with synchronous provider ' . $provider->getId(), ['exception' => $e]);
					$this->taskProcessingManager->setTaskResult($task->getId(), $e->getMessage(), null);
					// Schedule again
					$this->jobList->add(self::class, $argument);
					return;
				}
				try {
					$this->taskProcessingManager->setTaskStatus($task, Task::STATUS_RUNNING);
					$output = $provider->process($task->getUserId(), $input, fn (float $progress) => $this->taskProcessingManager->setTaskProgress($task->getId(), $progress));
				} catch (ProcessingException $e) {
					$this->logger->warning('Failed to process a TaskProcessing task with synchronous provider ' . $provider->getId(), ['exception' => $e]);
					$this->taskProcessingManager->setTaskResult($task->getId(), $e->getMessage(), null);
					// Schedule again
					$this->jobList->add(self::class, $argument);
					return;
				} catch (\Throwable $e) {
					$this->logger->error('Unknown error while processing TaskProcessing task', ['exception' => $e]);
					$this->taskProcessingManager->setTaskResult($task->getId(), $e->getMessage(), null);
					// Schedule again
					$this->jobList->add(self::class, $argument);
					return;
				}
				$this->taskProcessingManager->setTaskResult($task->getId(), null, $output);
			} catch (NotFoundException $e) {
				$this->logger->info('Could not find task anymore after execution. Moving on.', ['exception' => $e]);
			} catch (Exception $e) {
				$this->logger->error('Failed to report result of TaskProcessing task', ['exception' => $e]);
			}
		}

		$synchronousProviders = array_filter($providers, fn ($provider) =>
			$provider instanceof ISynchronousProvider);
		$taskTypes = array_values(array_map(fn ($provider) =>
			$provider->getTaskTypeId(),
			$synchronousProviders
		));
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
