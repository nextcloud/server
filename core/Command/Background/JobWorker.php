<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Background;

use OC\Core\Command\InterruptedException;
use OC\Files\SetupManager;
use OCP\BackgroundJob\IJobList;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class JobWorker extends JobBase {

	public function __construct(
		protected IJobList $jobList,
		protected LoggerInterface $logger,
		private ITempManager $tempManager,
		private SetupManager $setupManager,
	) {
		parent::__construct($jobList, $logger);
	}
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
				5
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
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

		while (true) {
			// Handle canceling of the process
			try {
				$this->abortIfInterrupted();
			} catch (InterruptedException $e) {
				$output->writeln('<info>Background job worker stopped</info>');
				break;
			}

			$this->printSummary($input, $output);

			usleep(50000);
			$job = $this->jobList->getNext(false, $jobClasses);
			if (!$job) {
				if ($input->getOption('once') === true) {
					if ($jobClasses === null) {
						$output->writeln('No job is currently queued', OutputInterface::VERBOSITY_VERBOSE);
					} else {
						$output->writeln('No job of classes [' . implode(', ', $jobClasses) . '] is currently queued', OutputInterface::VERBOSITY_VERBOSE);
					}
					$output->writeln('Exiting...', OutputInterface::VERBOSITY_VERBOSE);
					break;
				}

				$output->writeln('Waiting for new jobs to be queued', OutputInterface::VERBOSITY_VERBOSE);
				// Re-check interval for new jobs
				sleep(1);
				continue;
			}

			$output->writeln('Running job ' . get_class($job) . ' with ID ' . $job->getId());

			if ($output->isVerbose()) {
				$this->printJobInfo($job->getId(), $job, $output);
			}

			/** @psalm-suppress DeprecatedMethod Calling execute until it is removed, then will switch to start */
			$job->execute($this->jobList);

			$output->writeln('Job ' . $job->getId() . ' has finished', OutputInterface::VERBOSITY_VERBOSE);

			// clean up after unclean jobs
			$this->setupManager->tearDown();
			$this->tempManager->clean();

			$this->jobList->setLastJob($job);
			$this->jobList->unlockJob($job);

			if ($input->getOption('once') === true) {
				break;
			}
		}

		return 0;
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
}
