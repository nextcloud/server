<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022, Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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
			$output->writeln("\n<comment>Output is currently limited to " .  $limit . " jobs. Specify `-l, --limit[=LIMIT]` to override.</comment>");
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
