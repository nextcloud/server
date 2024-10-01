<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\TaskProcessing;

use OC\Core\Command\Base;
use OCP\TaskProcessing\IManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends Base {
	public function __construct(
		protected IManager $taskProcessingManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('taskprocessing:task:get')
			->setDescription('Display all information for a specific task')
			->addArgument(
				'task-id',
				InputArgument::REQUIRED,
				'ID of the task to display'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$taskId = (int)$input->getArgument('task-id');
		$task = $this->taskProcessingManager->getTask($taskId);
		$jsonTask = $task->jsonSerialize();
		$jsonTask['error_message'] = $task->getErrorMessage();
		$this->writeArrayInOutputFormat($input, $output, $jsonTask);
		return 0;
	}
}
