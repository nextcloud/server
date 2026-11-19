<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Background;

use DateTimeImmutable;
use OC\BackgroundJob\JobClassesRegistry;
use OC\BackgroundJob\JobRuns;
use OC\Core\Command\InterruptedException;
use OCP\BackgroundJob\IJobList;
use OCP\Files\ISetupManager;
use OCP\IDBConnection;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class JobWorker extends JobBase {
	private int $forkCount = 0;
	private ?int $stopAfterSeconds;
	private ?int $startTime;

	public function __construct(
		protected IJobList $jobList,
		protected LoggerInterface $logger,
		private ITempManager $tempManager,
		private ISetupManager $setupManager,
		private readonly JobRuns $jobRuns,
		private readonly JobClassesRegistry $jobClassesRegistry,
		private readonly IDBConnection $connection,
	) {
		parent::__construct($jobList, $logger);
	}

	#[\Override]
	protected function configure(): void {
		parent::configure();

		$this
			->setName('background-job:worker')
			->setDescription('Run a background job worker')
			->addArgument(
				'job-classes',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'The classes of the jobs to look for in the database'
			)
			->addOption(
				'once',
				null,
				InputOption::VALUE_NONE,
				'Only execute the worker once (as a regular cron execution would do it)'
			)
			->addOption(
				'interval',
				'i',
				InputOption::VALUE_OPTIONAL,
				'Interval in seconds in which the worker should repeat already processed jobs (set to 0 for no repeat)',
				1
			)
			->addOption(
				'thread',
				'j',
				InputOption::VALUE_REQUIRED,
				'create multiple thread',
				1
			)
			->addOption(
				'stop_after',
				't',
				InputOption::VALUE_OPTIONAL,
				'Duration after which the worker should stop and exit. The worker won\'t kill a potential running job, it will exit after this job has finished running (supported values are: "30" or "30s" for 30 seconds, "10m" for 10 minutes and "2h" for 2 hours)'
			)
		;
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->startTime = time();
		$stopAfterOptionValue = $input->getOption('stop_after');
		$this->stopAfterSeconds = $stopAfterOptionValue === null
			? null
			: $this->parseStopAfter($stopAfterOptionValue);
		if ($this->stopAfterSeconds !== null) {
			$output->writeln('<info>Background job worker will stop after ' . $this->stopAfterSeconds . ' seconds</info>');
		}

		$jobClasses = $input->getArgument('job-classes');
		$jobClasses = empty($jobClasses) ? null : $jobClasses;

		if ($jobClasses !== null) {
			// at least one class is invalid
			foreach ($jobClasses as $jobClass) {
				if (!class_exists($jobClass)) {
					$output->writeln('<error>Invalid job class: ' . $jobClass . '</error>');
					return 1;
				}
			}
		}

		$multiThread = $input->getOption('thread') ?? 0;
		if ($multiThread > 1) {
			if (!extension_loaded('posix')) {
				throw new InvalidOptionException('posix extension is required to use --thread');
			}

			while (true) {
				usleep(10000); // not needed but still better to slightly desync
				$pid = pcntl_fork();
				// work around as the parent database connection is inherited by the child.
				// when child process is over, parent process database connection will drop.
				// The drop can happen anytime, even in the middle of a running request.
				// work around is to close the connection as soon as possible after forking.
				$this->connection->close();

				if ($pid === -1) {
					// TODO: manage issue while forking
				} elseif ($pid === 0) {
					$color = $this->createRandomColor();
					$this->runWorker($input, $output, $jobClasses, "<bg={$color}> </>  ");
					exit();
				} else {
					// main process, counting forks
					$this->forkCount++;
					while (true) {
						// Handle canceling of the process
						try {
							$this->abortIfInterrupted();
						} catch (InterruptedException) {
							return 0;
						}

						if (pcntl_waitpid(0, $status, WNOHANG) !== 0) {
							$this->forkCount--;
						}
						if ($this->forkCount < $multiThread) {
							break;
						}
						usleep(50000);
					}
				}
			}
		} else {
			$this->runWorker($input, $output, $jobClasses);
		}

		$this->waitForChild();
		return 0;
	}


	private function runWorker(
		InputInterface $input,
		OutputInterface $output,
		?array $jobClasses,
		string $prefix = ''): void {

		while (true) {
			// Stop if we exceeded stop_after value
			if ($this->stopAfterSeconds !== null && ($this->startTime + $this->stopAfterSeconds) < time()) {
				$output->writeln($prefix . 'stop_after time has been exceeded, exiting...', OutputInterface::VERBOSITY_VERBOSE);
				break;
			}
			// Handle canceling of the process
			try {
				$this->abortIfInterrupted();
			} catch (InterruptedException) {
				$output->writeln($prefix . '<info>Background job worker stopped</info>');
				return;
			}

			$this->printSummary($input, $output);

			usleep(50000);
			$job = $this->jobList->getNext(false, $jobClasses);
			if (!$job) {
				if ($input->getOption('once') === true) {
					if ($jobClasses === null) {
						$output->writeln($prefix . 'No job is currently queued', OutputInterface::VERBOSITY_VERBOSE);
					} else {
						$output->writeln($prefix . 'No job of classes [' . implode(', ', $jobClasses) . '] is currently queued', OutputInterface::VERBOSITY_VERBOSE);
					}
					$output->writeln($prefix . 'Exiting...', OutputInterface::VERBOSITY_VERBOSE);
					break;
				}

				$output->writeln($prefix . 'Waiting for new jobs to be queued', OutputInterface::VERBOSITY_VERBOSE);
				if ((int)$input->getOption('interval') === 0) {
					break;
				}
				// Re-check interval for new jobs
				sleep((int)$input->getOption('interval'));
				continue;
			}

			$jobClassName = get_class($job);
			$now = new DateTimeImmutable();

			if ($input->getOption('output') === 'row') {
				$output->writeln($prefix . '  ' . $now->format('Y-m-d H:i:s.v') . ' | ' . str_pad($job->getId(), 20) . ' | ' . str_pad((string)$job->getLastRun(), 14) . ' | ' . $jobClassName);
			} else {
				$output->writeln($prefix . 'Running job ' . $jobClassName . ' with ID ' . $job->getId() . ' ' . $job->getLastRun());
			}

			if ($output->isVerbose()) {
				$this->printJobInfo($job->getId(), $job, $output);
			}

			memory_reset_peak_usage();
			$jobClassId = $this->jobClassesRegistry->getId($jobClassName);
			$jobRunId = $this->jobRuns->started($jobClassId);
			$jobStartTime = microtime(true);
			$job->start($this->jobList);
			$timeSpent = microtime(true) - $jobStartTime;
			$jobMemoryPeak = memory_get_peak_usage();
			// TODO Job failure will never be catched here because exceptions are catched within $job->start method
			// The error will only be visible in server logs.
			// It should be a temporary state until a proper job runner is implemented.
			$this->jobRuns->finished($jobRunId, (int)($timeSpent * 1000), (int)($jobMemoryPeak / 1024));

			$output->writeln($prefix . 'Job ' . $job->getId() . ' has finished', OutputInterface::VERBOSITY_VERBOSE);

			// clean up after unclean jobs
			$this->setupManager->tearDown();
			$this->tempManager->clean();

			$this->jobList->setLastJob($job);
			$this->jobList->unlockJob($job);

			if ($input->getOption('once') === true) {
				break;
			}
		}
	}

	private function printSummary(InputInterface $input, OutputInterface $output): void {
		if (!$output->isVeryVerbose()) {
			return;
		}
		$output->writeln('<comment>Summary</comment>');

		$counts = [];
		foreach ($this->jobList->countByClass() as $row) {
			$counts[] = $row;
		}
		$this->writeTableInOutputFormat($input, $output, $counts);
	}

	private function parseStopAfter(string $value): ?int {
		if (is_numeric($value)) {
			return (int)$value;
		}
		if (preg_match("/^(\d+)s$/i", $value, $matches)) {
			return (int)$matches[0];
		}
		if (preg_match("/^(\d+)m$/i", $value, $matches)) {
			return 60 * ((int)$matches[0]);
		}
		if (preg_match("/^(\d+)h$/i", $value, $matches)) {
			return 60 * 60 * ((int)$matches[0]);
		}
		return null;
	}

	public function waitForChild(): void {
		if (!extension_loaded('posix')) {
			return;
		}

		while (pcntl_waitpid(0, $status) !== -1) {
		}
	}

	public function createRandomColor(): string {
		return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
	}
}
