<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\TaskProcessing;

use OC\Core\Command\Base;
use OCP\IConfig;
use OCP\TaskProcessing\IManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnabledCommand extends Base {
	public function __construct(
		protected IManager $taskProcessingManager,
		private IConfig $config,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('taskprocessing:task-type:set-enabled')
			->setDescription('Enable or disable a task type')
			->addArgument(
				'task-type-id',
				InputArgument::REQUIRED,
				'ID of the task type to configure'
			)
			->addArgument(
				'enabled',
				InputArgument::REQUIRED,
				'status of the task type availability. Set 1 to enable and 0 to disable.'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$enabled = (bool)$input->getArgument('enabled');
		$taskType = $input->getArgument('task-type-id');
		$json = $this->config->getAppValue('core', 'ai.taskprocessing_type_preferences');
		try {
			if ($json === '') {
				$taskTypeSettings = [];
			} else {
				$taskTypeSettings = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
			}
			
			$taskTypeSettings[$taskType] = $enabled;
			
			$this->config->setAppValue('core', 'ai.taskprocessing_type_preferences', json_encode($taskTypeSettings));
			$this->writeArrayInOutputFormat($input, $output, $taskTypeSettings);
			return 0;
		} catch (\JsonException $e) {
			throw new \JsonException('Error in TaskType DB entry');
		}
		
	}
}
