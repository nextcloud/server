<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\TaskProcessing;

use OC\Core\Command\Base;
use OC\TaskProcessing\Db\TaskMapper;
use OC\TaskProcessing\Manager;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Cleanup extends Base {
	private IAppData $appData;

	public function __construct(
		protected Manager $taskProcessingManager,
		private TaskMapper $taskMapper,
		private LoggerInterface $logger,
		IAppDataFactory $appDataFactory,
	) {
		parent::__construct();
		$this->appData = $appDataFactory->get('core');
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
		$output->writeln('<comment>Cleanup up tasks older than ' . $maxAgeSeconds . ' seconds and the related output files</comment>');

		$taskIdsToCleanup = [];
		try {
			$fileCleanupGenerator = $this->taskProcessingManager->cleanupTaskProcessingTaskFiles($maxAgeSeconds);
			foreach ($fileCleanupGenerator as $cleanedUpEntry) {
				$output->writeln(
					"<info>\t - " . 'Deleted appData/core/TaskProcessing/' . $cleanedUpEntry['file_name']
					. ' (fileId: ' . $cleanedUpEntry['file_id'] . ', taskId: ' . $cleanedUpEntry['task_id'] . ')</info>'
				);
			}
			$taskIdsToCleanup = $fileCleanupGenerator->getReturn();
		} catch (\Exception $e) {
			$this->logger->warning('Failed to delete stale task processing tasks files', ['exception' => $e]);
			$output->writeln('<warning>Failed to delete stale task processing tasks files</warning>');
		}
		try {
			$deletedTaskCount = $this->taskMapper->deleteOlderThan($maxAgeSeconds);
			foreach ($taskIdsToCleanup as $taskId) {
				$output->writeln("<info>\t - " . 'Deleted task ' . $taskId . ' from the database</info>');
			}
			$output->writeln("<comment>\t - " . 'Deleted ' . $deletedTaskCount . ' tasks from the database</comment>');
		} catch (\OCP\DB\Exception $e) {
			$this->logger->warning('Failed to delete stale task processing tasks', ['exception' => $e]);
			$output->writeln('<warning>Failed to delete stale task processing tasks</warning>');
		}
		try {
			$textToImageDeletedFileNames = $this->taskProcessingManager->clearFilesOlderThan($this->appData->getFolder('text2image'), $maxAgeSeconds);
			foreach ($textToImageDeletedFileNames as $entry) {
				$output->writeln("<info>\t - " . 'Deleted appData/core/text2image/' . $entry . '</info>');
			}
		} catch (NotFoundException $e) {
			// noop
		}
		try {
			$audioToTextDeletedFileNames = $this->taskProcessingManager->clearFilesOlderThan($this->appData->getFolder('audio2text'), $maxAgeSeconds);
			foreach ($audioToTextDeletedFileNames as $entry) {
				$output->writeln("<info>\t - " . 'Deleted appData/core/audio2text/' . $entry . '</info>');
			}
		} catch (NotFoundException $e) {
			// noop
		}

		return 0;
	}
}
