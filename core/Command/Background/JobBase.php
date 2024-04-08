<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


namespace OC\Core\Command\Background;

use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\IJobList;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class JobBase extends \OC\Core\Command\Base {
	protected IJobList $jobList;
	protected LoggerInterface $logger;

	public function __construct(
		IJobList $jobList,
		LoggerInterface $logger
	) {
		parent::__construct();
		$this->jobList = $jobList;
		$this->logger = $logger;
	}

	protected function printJobInfo(int $jobId, IJob $job, OutputInterface $output): void {
		$row = $this->jobList->getDetailsById($jobId);

		if ($row === null) {
			return;
		}

		$lastRun = new \DateTime();
		$lastRun->setTimestamp((int) $row['last_run']);
		$lastChecked = new \DateTime();
		$lastChecked->setTimestamp((int) $row['last_checked']);
		$reservedAt = new \DateTime();
		$reservedAt->setTimestamp((int) $row['reserved_at']);

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
		if ((int) $row['reserved_at'] === 0) {
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
