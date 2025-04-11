<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OC\Core\Command\Background;

use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\IJobList;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class JobBase extends \OC\Core\Command\Base {

	public function __construct(
		protected IJobList $jobList,
		protected LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function printJobInfo(int $jobId, IJob $job, OutputInterface $output): void {
		$row = $this->jobList->getDetailsById($jobId);

		if ($row === null) {
			return;
		}

		$lastRun = new \DateTime();
		$lastRun->setTimestamp((int)$row['last_run']);
		$lastChecked = new \DateTime();
		$lastChecked->setTimestamp((int)$row['last_checked']);
		$reservedAt = new \DateTime();
		$reservedAt->setTimestamp((int)$row['reserved_at']);

		$output->writeln('Job class:            ' . get_class($job));
		$output->writeln('Arguments:            ' . json_encode($job->getArgument()));

		$isTimedJob = $job instanceof \OCP\BackgroundJob\TimedJob;
		if ($isTimedJob) {
			$output->writeln('Type:                 timed');
		} elseif ($job instanceof \OCP\BackgroundJob\QueuedJob) {
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
			$nextRun->setTimestamp((int)$row['last_run'] + $interval);

			if ($nextRun > new \DateTime()) {
				$output->writeln('Next execution:       <comment>' . $nextRun->format(\DateTimeInterface::ATOM) . '</comment>');
			} else {
				$output->writeln('Next execution:       <info>' . $nextRun->format(\DateTimeInterface::ATOM) . '</info>');
			}
		}
	}
}
