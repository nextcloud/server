<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\TaskProcessing;

use OC\Core\Command\Base;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\Task;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Statistics extends Base {
	public function __construct(
		protected IManager $taskProcessingManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('taskprocessing:task:stats')
			->setDescription('get statistics for tasks')
			->addOption(
				'userIdFilter',
				'u',
				InputOption::VALUE_OPTIONAL,
				'only get the tasks for one user ID'
			)
			->addOption(
				'type',
				't',
				InputOption::VALUE_OPTIONAL,
				'only get the tasks for one task type'
			)
			->addOption(
				'appId',
				null,
				InputOption::VALUE_OPTIONAL,
				'only get the tasks for one app ID'
			)
			->addOption(
				'customId',
				null,
				InputOption::VALUE_OPTIONAL,
				'only get the tasks for one custom ID'
			)
			->addOption(
				'status',
				's',
				InputOption::VALUE_OPTIONAL,
				'only get the tasks that have a specific status (STATUS_UNKNOWN=0, STATUS_SCHEDULED=1, STATUS_RUNNING=2, STATUS_SUCCESSFUL=3, STATUS_FAILED=4, STATUS_CANCELLED=5)'
			)
			->addOption(
				'scheduledAfter',
				null,
				InputOption::VALUE_OPTIONAL,
				'only get the tasks that were scheduled after a specific date (Unix timestamp)'
			)
			->addOption(
				'endedBefore',
				null,
				InputOption::VALUE_OPTIONAL,
				'only get the tasks that ended before a specific date (Unix timestamp)'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userIdFilter = $input->getOption('userIdFilter');
		if ($userIdFilter === null) {
			$userIdFilter = '';
		} elseif ($userIdFilter === '') {
			$userIdFilter = null;
		}
		$type = $input->getOption('type');
		$appId = $input->getOption('appId');
		$customId = $input->getOption('customId');
		$status = $input->getOption('status');
		$scheduledAfter = $input->getOption('scheduledAfter');
		$endedBefore = $input->getOption('endedBefore');

		$tasks = $this->taskProcessingManager->getTasks($userIdFilter, $type, $appId, $customId, $status, $scheduledAfter, $endedBefore);

		$stats = ['Number of tasks' => count($tasks)];

		$maxRunningTime = 0;
		$totalRunningTime = 0;
		$runningTimeCount = 0;

		$maxQueuingTime = 0;
		$totalQueuingTime = 0;
		$queuingTimeCount = 0;

		$maxUserWaitingTime = 0;
		$totalUserWaitingTime = 0;
		$userWaitingTimeCount = 0;

		$maxInputSize = 0;
		$maxOutputSize = 0;
		$inputCount = 0;
		$inputSum = 0;
		$outputCount = 0;
		$outputSum = 0;

		foreach ($tasks as $task) {
			// running time
			if ($task->getStartedAt() !== null && $task->getEndedAt() !== null) {
				$taskRunningTime = $task->getEndedAt() - $task->getStartedAt();
				$totalRunningTime += $taskRunningTime;
				$runningTimeCount++;
				if ($taskRunningTime >= $maxRunningTime) {
					$maxRunningTime = $taskRunningTime;
				}
			}
			// queuing time
			if ($task->getScheduledAt() !== null && $task->getStartedAt() !== null) {
				$taskQueuingTime = $task->getStartedAt() - $task->getScheduledAt();
				$totalQueuingTime += $taskQueuingTime;
				$queuingTimeCount++;
				if ($taskQueuingTime >= $maxQueuingTime) {
					$maxQueuingTime = $taskQueuingTime;
				}
			}
			// user waiting time
			if ($task->getScheduledAt() !== null && $task->getEndedAt() !== null) {
				$taskUserWaitingTime = $task->getEndedAt() - $task->getScheduledAt();
				$totalUserWaitingTime += $taskUserWaitingTime;
				$userWaitingTimeCount++;
				if ($taskUserWaitingTime >= $maxUserWaitingTime) {
					$maxUserWaitingTime = $taskUserWaitingTime;
				}
			}
			// input/output sizes
			if ($task->getStatus() === Task::STATUS_SUCCESSFUL) {
				$outputString = json_encode($task->getOutput());
				if ($outputString !== false) {
					$outputCount++;
					$outputLength = strlen($outputString);
					$outputSum += $outputLength;
					if ($outputLength > $maxOutputSize) {
						$maxOutputSize = $outputLength;
					}
				}
			}
			$inputString = json_encode($task->getInput());
			if ($inputString !== false) {
				$inputCount++;
				$inputLength = strlen($inputString);
				$inputSum += $inputLength;
				if ($inputLength > $maxInputSize) {
					$maxInputSize = $inputLength;
				}
			}
		}

		if ($runningTimeCount > 0) {
			$stats['Max running time'] = $maxRunningTime;
			$averageRunningTime = $totalRunningTime / $runningTimeCount;
			$stats['Average running time'] = (int)$averageRunningTime;
			$stats['Running time count'] = $runningTimeCount;
		}
		if ($queuingTimeCount > 0) {
			$stats['Max queuing time'] = $maxQueuingTime;
			$averageQueuingTime = $totalQueuingTime / $queuingTimeCount;
			$stats['Average queuing time'] = (int)$averageQueuingTime;
			$stats['Queuing time count'] = $queuingTimeCount;
		}
		if ($userWaitingTimeCount > 0) {
			$stats['Max user waiting time'] = $maxUserWaitingTime;
			$averageUserWaitingTime = $totalUserWaitingTime / $userWaitingTimeCount;
			$stats['Average user waiting time'] = (int)$averageUserWaitingTime;
			$stats['User waiting time count'] = $userWaitingTimeCount;
		}
		if ($outputCount > 0) {
			$stats['Max output size (bytes)'] = $maxOutputSize;
			$averageOutputSize = $outputSum / $outputCount;
			$stats['Average output size (bytes)'] = (int)$averageOutputSize;
			$stats['Number of tasks with output'] = $outputCount;
		}
		if ($inputCount > 0) {
			$stats['Max input size (bytes)'] = $maxInputSize;
			$averageInputSize = $inputSum / $inputCount;
			$stats['Average input size (bytes)'] = (int)$averageInputSize;
			$stats['Number of tasks with input'] = $inputCount;
		}

		$this->writeArrayInOutputFormat($input, $output, $stats);
		return 0;
	}
}
