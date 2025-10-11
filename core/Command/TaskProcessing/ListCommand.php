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

class ListCommand extends Base {
	public function __construct(
		protected IManager $taskProcessingManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('taskprocessing:task:list')
			->setDescription('list tasks')
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
				'only get the tasks that have a specific status (STATUS_UNKNOWN=0, STATUS_SCHEDULED=1, STATUS_RUNNING=2, STATUS_SUCCESSFUL=3, STATUS_FAILED=4, STATUS_CANCELLED=5)',
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
		$status = $input->getOption('status') !== null ? (int)$input->getOption('status') : null;
		$scheduledAfter = $input->getOption('scheduledAfter') != null ? (int)$input->getOption('scheduledAfter') : null;
		$endedBefore = $input->getOption('endedBefore') !== null ? (int)$input->getOption('endedBefore') : null;

		$tasks = $this->taskProcessingManager->getTasks($userIdFilter, $type, $appId, $customId, $status, $scheduledAfter, $endedBefore);
		$arrayTasks = array_map(static function (Task $task) {
			$jsonTask = $task->jsonSerialize();
			$jsonTask['error_message'] = $task->getErrorMessage();
			return $jsonTask;
		}, $tasks);

		$this->writeArrayInOutputFormat($input, $output, $arrayTasks);
		return 0;
	}
}
