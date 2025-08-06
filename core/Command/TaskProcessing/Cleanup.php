<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\TaskProcessing;

use OC\Core\Command\Base;
use OC\TaskProcessing\Manager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Cleanup extends Base {
	public function __construct(
		protected Manager $taskProcessingManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('taskprocessing:task:cleanup')
			->setDescription('cleanup old tasks')
			->addArgument(
				'maxAgeSeconds',
				InputArgument::OPTIONAL,
				// default is not defined as an argument default value because we want to show a nice "4 months" value
				'delete tasks that are older than this number of seconds, defaults to ' . Manager::MAX_TASK_AGE_SECONDS . ' (4 months)',
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$maxAgeSeconds = $input->getArgument('maxAgeSeconds') ?? Manager::MAX_TASK_AGE_SECONDS;
		$output->writeln('Cleanup up tasks older than '. $maxAgeSeconds . ' seconds and the related output files');
		$cleanupResult = $this->taskProcessingManager->cleanupOldTasks($maxAgeSeconds);
		foreach ($cleanupResult as $entry) {
			if (isset($entry['task_id'], $entry['file_id'], $entry['file_name'])) {
				$output->writeln("\t - " . 'Deleted appData/core/TaskProcessing/' . $entry['file_name'] . '(fileId: ' . $entry['file_id'] . ', taskId: ' . $entry['task_id'] . ')');
			} elseif (isset($entry['directory_name'])) {
				$output->writeln("\t - " . 'Deleted appData/core/'. $entry['directory_name'] . '/' . $entry['file_name']);
			} elseif (isset($entry['deleted_task_count'])) {
				$output->writeln("\t - " . 'Deleted '. $entry['deleted_task_count'] . ' tasks from the database');
			}
		}
		return 0;
	}
}
