<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Command\Background;

use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\IJobList;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Worker extends Command {
	protected IJobList $jobList;
	protected LoggerInterface $logger;

	const DEFAULT_INTERVAL = 5;

	public function __construct(IJobList $jobList,
								LoggerInterface $logger) {
		parent::__construct();
		$this->jobList = $jobList;
		$this->logger = $logger;
	}

	protected function configure(): void {
		$this
			->setName('background-job:worker')
			->setDescription('Run a background job worker')
			->addArgument(
				'job-class',
				InputArgument::OPTIONAL,
				'The class of the job in the database'
			)
			->addOption(
				'once',
				null,
				InputOption::VALUE_NONE,
				'Only execute the worker once (as a regular cron execution would do it)'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$jobClass = $input->getArgument('job-class');

		$executedJobs = [];

		$ended = false;
		pcntl_signal(SIGINT, function () use (&$ended, $output, $executedJobs) {
			$output->writeln('SIGINT');
			if ($ended) {
				foreach ($executedJobs as $id => $time) {
					unset($executedJobs[$id]);
					$job = $this->jobList->getById($id);
					$this->jobList->unlockJob($job);
				}
				$output->writeln('<error>Killed');
				exit(1);
			}
			$ended = true;
			$output->writeln('<comment>Waiting for job to finish. Press Ctrl-C again to kill, but this may have unexpected side effects.</comment>');
		});

		while (true) {
			if ($ended) {
				break;
			}
			$count = 0;
			$total = 0;
			foreach($this->jobList->countByClass() as $row) {
				if ((int)$row['count'] === 1) {
					$count++;
				} else {
					$output->writeln($row['class'] . "    " . $row['count']);
				}
				$total += $row['count'];
			}
			$output->writeln("Other jobs " . $count);
			$output->writeln("Total jobs " . $count);



			foreach ($executedJobs as $id => $time) {
				if ($time < time() - self::DEFAULT_INTERVAL) {
					unset($executedJobs[$id]);
					$job = $this->jobList->getById($id);
					$this->jobList->unlockJob($job);
				}
			}

			$job = $this->jobList->getNext(false, $jobClass);
			if (!$job) {
				$output->writeln("Waiting for new jobs to be queued");
				sleep(1);
				continue;
			}


			if (isset($executedJobs[$job->getId()])) {
				continue;
			}

			$output->writeln("- Running job " . get_class($job) . " " . $job->getId());

			if ($output->isVerbose()) {
				$this->printJobInfo($job->getId(), $job, $output);
			}

			$job->execute($this->jobList, \OC::$server->getLogger());

			// clean up after unclean jobs
			\OC_Util::tearDownFS();
			\OC::$server->getTempManager()->clean();

			$this->jobList->setLastJob($job);
			$executedJobs[$job->getId()] = time();
			unset($job);

			if ($input->getOption('once')) {
				break;
			}
		}

		foreach ($executedJobs as $id => $time) {
			unset($executedJobs[$id]);
			$job = $this->jobList->getById($id);
			$this->jobList->unlockJob($job);
		}

		return 0;
	}

	protected function printJobInfo(int $jobId, IJob $job, OutputInterface$output): void {
		$row = $this->jobList->getDetailsById($jobId);

		$lastRun = new \DateTime();
		$lastRun->setTimestamp((int) $row['last_run']);
		$lastChecked = new \DateTime();
		$lastChecked->setTimestamp((int) $row['last_checked']);
		$reservedAt = new \DateTime();
		$reservedAt->setTimestamp((int) $row['reserved_at']);

		$output->writeln('Job class:            ' . get_class($job));
		$output->writeln('Arguments:            ' . json_encode($job->getArgument()));

		$isTimedJob = $job instanceof \OC\BackgroundJob\TimedJob || $job instanceof \OCP\BackgroundJob\TimedJob;
		if ($isTimedJob) {
			$output->writeln('Type:                 timed');
		} elseif ($job instanceof \OC\BackgroundJob\QueuedJob || $job instanceof \OCP\BackgroundJob\QueuedJob) {
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
			$nextRun->setTimestamp($row['last_run'] + $interval);

			if ($nextRun > new \DateTime()) {
				$output->writeln('Next execution:       <comment>' . $nextRun->format(\DateTimeInterface::ATOM) . '</comment>');
			} else {
				$output->writeln('Next execution:       <info>' . $nextRun->format(\DateTimeInterface::ATOM) . '</info>');
			}
		}
	}
}
