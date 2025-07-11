<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Background;

use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\BackgroundJob\TimedJob;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Job extends Command {
	public function __construct(
		protected IJobList $jobList,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('background-job:execute')
			->setDescription('Execute a single background job manually')
			->addArgument(
				'job-id',
				InputArgument::REQUIRED,
				'The ID of the job in the database'
			)
			->addOption(
				'force-execute',
				null,
				InputOption::VALUE_NONE,
				'Force execute the background job, independent from last run and being reserved'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$jobId = (int)$input->getArgument('job-id');

		$job = $this->jobList->getById($jobId);
		if ($job === null) {
			$output->writeln('<error>Job with ID ' . $jobId . ' could not be found in the database</error>');
			return 1;
		}

		$this->printJobInfo($jobId, $job, $output);
		$output->writeln('');

		$lastRun = $job->getLastRun();
		if ($input->getOption('force-execute')) {
			$lastRun = 0;
			$output->writeln('<comment>Forcing execution of the job</comment>');
			$output->writeln('');

			$this->jobList->resetBackgroundJob($job);
		}

		$job = $this->jobList->getById($jobId);
		if ($job === null) {
			$output->writeln('<error>Something went wrong when trying to retrieve Job with ID ' . $jobId . ' from database</error>');
			return 1;
		}
		/** @psalm-suppress DeprecatedMethod Calling execute until it is removed, then will switch to start */
		$job->execute($this->jobList);
		$job = $this->jobList->getById($jobId);

		if (($job === null) || ($lastRun !== $job->getLastRun())) {
			$output->writeln('<info>Job executed!</info>');
			$output->writeln('');

			if ($job instanceof TimedJob) {
				$this->printJobInfo($jobId, $job, $output);
			}
		} else {
			$output->writeln('<comment>Job was not executed because it is not due</comment>');
			$output->writeln('Specify the <question>--force-execute</question> option to run it anyway');
		}

		return 0;
	}

	protected function printJobInfo(int $jobId, IJob $job, OutputInterface $output): void {
		$row = $this->jobList->getDetailsById($jobId);

		$lastRun = new \DateTime();
		$lastRun->setTimestamp((int)$row['last_run']);
		$lastChecked = new \DateTime();
		$lastChecked->setTimestamp((int)$row['last_checked']);
		$reservedAt = new \DateTime();
		$reservedAt->setTimestamp((int)$row['reserved_at']);

		$output->writeln('Job class:            ' . get_class($job));
		$output->writeln('Arguments:            ' . json_encode($job->getArgument()));

		$isTimedJob = $job instanceof TimedJob;
		if ($isTimedJob) {
			$output->writeln('Type:                 timed');
		} elseif ($job instanceof QueuedJob) {
			$output->writeln('Type:                 queued');
		} else {
			$output->writeln('Type:                 job');
		}

		$output->writeln('');
		$output->writeln('Last checked:         ' . $lastChecked->format(\DateTimeInterface::ATOM));
		if ((int)$row['reserved_at'] === 0) {
			$output->writeln('Reserved at:          -');
		} else {
			$output->writeln('Reserved at:          <comment>' . $reservedAt->format(\DateTimeInterface::ATOM) . '</comment>');
		}
		$output->writeln('Last executed:        ' . $lastRun->format(\DateTimeInterface::ATOM));
		$output->writeln('Last duration:        ' . $row['execution_duration']);

		if ($isTimedJob) {
			$reflection = new \ReflectionClass($job);
			$intervalProperty = $reflection->getProperty('interval');
			$intervalProperty->setAccessible(true);
			$interval = $intervalProperty->getValue($job);

			$nextRun = new \DateTime();
			$nextRun->setTimestamp($row['last_run'] + $interval);

			if ($nextRun > new \DateTime()) {
				$output->writeln('Next execution:       <comment>' . $nextRun->format(\DateTimeInterface::ATOM) . '</comment>');
			} else {
				$output->writeln('Next execution:       <info>' . $nextRun->format(\DateTimeInterface::ATOM) . '</info>');
			}
		}
	}
}
