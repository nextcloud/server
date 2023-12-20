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

use OC\Core\Command\InterruptedException;
use OCP\BackgroundJob\IJobList;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class JobWorker extends JobBase {
	private array $executedJobs = [];

	protected function configure(): void {
		parent::configure();

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
		$jobClass = $input->getArgument('job-class');

		if ($jobClass && !class_exists($jobClass)) {
			$output->writeln('<error>Invalid job class</error>');
			return 1;
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
			$job = $this->jobList->getNext(false, $jobClass);
			if (!$job) {
				if ($input->getOption('once') === true) {
					break;
				}

				$output->writeln("Waiting for new jobs to be queued", OutputInterface::VERBOSITY_VERBOSE);
				// Re-check interval for new jobs
				sleep(1);
				continue;
			}

			$output->writeln("Running job " . get_class($job) . " with ID " . $job->getId());

			if ($output->isVerbose()) {
				$this->printJobInfo($job->getId(), $job, $output);
			}

			$job->execute($this->jobList, \OC::$server->getLogger());

			// clean up after unclean jobs
			\OC_Util::tearDownFS();
			\OC::$server->getTempManager()->clean();

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
		$output->writeln("<comment>Summary</comment>");

		$counts = [];
		foreach ($this->jobList->countByClass() as $row) {
			$counts[] = $row;
		}
		$this->writeTableInOutputFormat($input, $output, $counts);
	}
}
