<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Background;

use OC\Core\Command\Base;
use OCP\BackgroundJob\IJobList;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Base {
	public function __construct(
		protected IJobList $jobList,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('background-job:list')
			->setDescription('List background jobs')
			->addOption(
				'class',
				'c',
				InputOption::VALUE_OPTIONAL,
				'Job class to search for',
				null
			)->addOption(
				'limit',
				'l',
				InputOption::VALUE_OPTIONAL,
				'Number of jobs to retrieve',
				'500'
			)->addOption(
				'offset',
				'o',
				InputOption::VALUE_OPTIONAL,
				'Offset for retrieving jobs',
				'0'
			)
		;
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$limit = (int)$input->getOption('limit');
		$jobsInfo = $this->formatJobs($this->jobList->getJobsIterator($input->getOption('class'), $limit, (int)$input->getOption('offset')));
		$this->writeTableInOutputFormat($input, $output, $jobsInfo);
		if ($input->getOption('output') === self::OUTPUT_FORMAT_PLAIN && count($jobsInfo) >= $limit) {
			$output->writeln("\n<comment>Output is currently limited to " . $limit . ' jobs. Specify `-l, --limit[=LIMIT]` to override.</comment>');
		}
		return 0;
	}

	protected function formatJobs(iterable $jobs): array {
		$jobsInfo = [];
		foreach ($jobs as $job) {
			$jobsInfo[] = [
				'id' => $job->getId(),
				'class' => get_class($job),
				'last_run' => date(DATE_ATOM, $job->getLastRun()),
				'argument' => json_encode($job->getArgument()),
			];
		}
		return $jobsInfo;
	}
}
