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

	#[\Override]
	protected function configure(): void {
		$this
			->setName('background-job:list')
			->setDescription('List background jobs')
			->addOption(
				'class',
				'c',
				InputOption::VALUE_REQUIRED,
				'Job class to search for',
				null
			)->addOption(
				'limit',
				'l',
				InputOption::VALUE_REQUIRED,
				'Number of jobs to retrieve',
				'500'
			)->addOption(
				'offset',
				'o',
				InputOption::VALUE_REQUIRED,
				'Offset for retrieving jobs',
				'0'
			)
			->addOption(
				'group',
				null,
				InputOption::VALUE_NONE,
				'Group jobs by class'
			)
		;
		parent::configure();
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$limit = (int)$input->getOption('limit');
		$offset = (int)$input->getOption('offset');
		$group = $input->getOption('group');
		if ($group) {
			$grouped = $this->jobList->countByClass($limit, $offset);
			$this->writeTableInOutputFormat($input, $output, $grouped);
		} else {
			$jobsInfo = $this->formatJobs($this->jobList->getJobsIterator($input->getOption('class'), $limit, $offset));
			$this->writeTableInOutputFormat($input, $output, $jobsInfo);
			if ($input->getOption('output') === self::OUTPUT_FORMAT_PLAIN && count($jobsInfo) >= $limit) {
				$output->writeln("\n<comment>Output is currently limited to " . $limit . ' jobs. Specify `-l, --limit[=LIMIT]` to override.</comment>');
			}
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
