<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\TaskProcessing;

use OC\Core\Command\Base;
use OC\Core\Command\InterruptedException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use OCP\TaskProcessing\Exception\Exception;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\ISynchronousProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends Base {
	public function __construct(
		private readonly IManager $taskProcessingManager,
		private readonly LoggerInterface $logger,
		private readonly IAppConfig $appConfig,
		private readonly ITimeFactory $timeFactory,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('taskprocessing:worker')
			->setDescription('Run a dedicated worker for synchronous TaskProcessing providers')
			->addOption(
				'timeout',
				't',
				InputOption::VALUE_OPTIONAL,
				'Duration in seconds after which the worker exits (0 = run indefinitely). You should regularly (e.g. every 5 minutes) restart this worker by using this option to make sure it picks up configuration changes.',
				0
			)
			->addOption(
				'interval',
				'i',
				InputOption::VALUE_OPTIONAL,
				'Sleep duration in seconds between polling iterations when no task was processed',
				1
			)
			->addOption(
				'once',
				null,
				InputOption::VALUE_NONE,
				'Process at most one task then exit'
			)
			->addOption(
				'taskTypes',
				null,
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Only process tasks of the given task type IDs (can be specified multiple times)'
			);
		parent::configure();
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$startTime = $this->timeFactory->now()->getTimestamp();
		$timeout = (int)$input->getOption('timeout');
		$interval = (int)$input->getOption('interval');
		$once = $input->getOption('once') === true;
		/** @var list<string> $taskTypes */
		$taskTypes = $input->getOption('taskTypes');
		$lastConfigStorageTime = 0;

		if ($timeout > 0) {
			$output->writeln('<info>Task processing worker will stop after ' . $timeout . ' seconds</info>');
		}

		while (true) {
			// Stop if timeout exceeded
			if ($timeout > 0 && ($startTime + $timeout) < $this->timeFactory->now()->getTimestamp()) {
				$output->writeln('Timeout reached, exiting...', OutputInterface::VERBOSITY_VERBOSE);
				break;
			}

			// Handle SIGTERM/SIGINT gracefully
			try {
				$this->abortIfInterrupted();
			} catch (InterruptedException) {
				$output->writeln('<info>Task processing worker stopped</info>');
				break;
			}

			if ($lastConfigStorageTime < $this->timeFactory->now()->getTimestamp() - 60) {
				$this->appConfig->setValueString('core', 'ai.taskprocessing_worker_last_iteration', (string)$this->timeFactory->now()->getTimestamp(), lazy: true);
				$lastConfigStorageTime = $this->timeFactory->now()->getTimestamp();
			}
			$processedTask = $this->processNextTask($output, $taskTypes);

			if ($once) {
				break;
			}

			if (!$processedTask) {
				$output->writeln('No task processed, waiting ' . $interval . ' second(s)...', OutputInterface::VERBOSITY_VERBOSE);
				sleep($interval);
			}
		}

		return 0;
	}

	/**
	 * Attempt to process one task across all preferred synchronous providers.
	 *
	 * To avoid starvation, all eligible task types are first collected and then
	 * the oldest scheduled task across all of them is claimed in a single atomic
	 * query (FOR UPDATE SKIP LOCKED, with a SQLite fallback). Each claim prefers the
	 * oldest available scheduled task -- under parallel workers SKIP LOCKED skips rows
	 * another worker has locked, so this reduces starvation rather than guaranteeing a
	 * strict global processing order -- and no two workers ever claim the same task.
	 *
	 * @param list<string> $taskTypes When non-empty, only providers for these task type IDs are considered.
	 * @return bool True if a task was processed, false if no task was found
	 */
	private function processNextTask(OutputInterface $output, array $taskTypes = []): bool {
		$providers = $this->taskProcessingManager->getProviders();

		// Build a map of eligible taskTypeId => provider for all preferred synchronous providers
		/** @var array<string, ISynchronousProvider> $eligibleProviders */
		$eligibleProviders = [];
		foreach ($providers as $provider) {
			if (!$provider instanceof ISynchronousProvider) {
				continue;
			}

			$taskTypeId = $provider->getTaskTypeId();

			// If a task type whitelist was provided, skip providers not in the list
			if (!empty($taskTypes) && !in_array($taskTypeId, $taskTypes, true)) {
				continue;
			}

			// Only use this provider if it is the preferred one for the task type
			try {
				$preferredProvider = $this->taskProcessingManager->getPreferredProvider($taskTypeId);
			} catch (Exception $e) {
				$this->logger->error('Failed to get preferred provider for task type ' . $taskTypeId, ['exception' => $e]);
				continue;
			}

			if ($provider->getId() !== $preferredProvider->getId()) {
				continue;
			}

			$eligibleProviders[$taskTypeId] = $provider;
		}

		if (empty($eligibleProviders)) {
			return false;
		}

		// Atomically claim the oldest scheduled task across all eligible task types in
		// one query. SELECT ... FOR UPDATE SKIP LOCKED (with a SQLite fallback) both
		// fetches and marks the task RUNNING, so multiple workers never claim the same
		// task and no per-worker ignore-list / retry loop is needed. This also reduces
		// starvation: each claim prefers the oldest available task, so a provider with a
		// large queue does not indefinitely block another provider's older tasks (though a
		// worker may claim a newer task while an older one is locked by another worker).
		try {
			$task = $this->taskProcessingManager->claimNextScheduledTask(array_keys($eligibleProviders));
		} catch (Exception $e) {
			$this->logger->error('Unknown error while claiming scheduled TaskProcessing tasks', ['exception' => $e]);
			return false;
		}

		if ($task === null) {
			// No schedulable task available right now.
			return false;
		}

		$taskTypeId = $task->getTaskTypeId();
		$provider = $eligibleProviders[$taskTypeId];

		$output->writeln(
			'Processing task ' . $task->getId() . ' of type ' . $taskTypeId . ' with provider ' . $provider->getId(),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$this->taskProcessingManager->processTask($task, $provider);

		$output->writeln(
			'Finished processing task ' . $task->getId(),
			OutputInterface::VERBOSITY_VERBOSE
		);

		return true;
	}
}
