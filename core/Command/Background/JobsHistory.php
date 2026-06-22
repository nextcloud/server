<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Background;

use OC\BackgroundJob\JobRuns;
use OC\Core\Command\Base;
use OCP\BackgroundJob\JobStatus;
use OCP\IServerInfo;
use OCP\Util;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ValueError;

final class JobsHistory extends Base {
	public function __construct(
		private readonly JobRuns $jobRuns,
		private readonly IServerInfo $serverInfo,
	) {
		parent::__construct();
	}

	#[Override]
	protected function configure(): void {
		parent::configure();

		$help = <<<EOF
			Display all currently running background jobs.

			You can find the following informations:
			  - <info>Run ID:</info> job identifier as found in database (Snowflake ID)
			  - <info>Class:</info> class of the job
			  - <info>Started at:</info> start time of the job
			  - <info>Server ID:</info> server ID as defined in <options=bold>config.php</> (see `serverid`). Highlighted if it’s running on current server.
			  - <info>PID:</info> PID of process executing the job
			  - <info>Duration:</info> human readable duration
			  - <info>Memory usage:</info> human readable memory usage peak

			EOF;

		$this
			->setName('background-job:history')
			->setDescription('Show currently running jobs')
			->setHelp($help)
			->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maximum number of results returned by the command', 200)
			->addOption('class', 'c', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Filter by class name', [])
			->addOption('status', 's', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Filter by status', []);
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$limit = (int)$input->getOption('limit');
		$classesId = $input->getOption('class');
		try {
			$statuses = array_map(fn (string $value) => JobStatus::from((int)$value), $input->getOption('status'));
		} catch (ValueError $e) {
			$output->writeln('<error>Invalid status provided</error>');
			$output->writeln($e->getMessage());
			return Base::FAILURE;
		}
		$jobs = $this->jobRuns->completedJobs($statuses, $classesId, $limit);
		$this->writeStreamingTableInOutputFormat($input, $output, $this->formatLine($jobs), 20);

		return Base::SUCCESS;
	}

	private function formatLine(iterable $jobs): \Generator {
		$jobsInfo = [];
		$now = time();
		$currentServerId = $this->serverInfo->getServerId();
		foreach ($jobs as $job) {
			$status = match ($job->status) {
				JobStatus::RUNNING => 'Running',
				JobStatus::SUCCEEDED => '<info>Succeeded</info>',
				JobStatus::FAILED => '<question>Failed</question>',
				JobStatus::CRASHED => '<error>Crashed</error>',
				default => 'Unknown',
			};
			yield [
				'Run ID' => $job->runId,
				'Status' => $status,
				'Class' => $job->className,
				'Started at' => $job->startedAt->format('Y-m-d H:i:s'),
				'Server ID' => $job->serverId === $currentServerId ? '<info>' . $job->serverId . '</info>' : $job->serverId,
				'PID' => $job->pid,
				'Duration' => $job->duration . ' ms',
				'Memory usage' => Util::humanFileSize($job->memoryPeak * 1024),
			];
		}

		return $jobsInfo;
	}
}
