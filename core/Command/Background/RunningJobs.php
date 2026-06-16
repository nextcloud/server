<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Background;

use OC\BackgroundJob\JobRuns;
use OC\Core\Command\Base;
use OCP\IServerInfo;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class RunningJobs extends Base {
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
			  - <info>Run ID:</info> job identifier a found in database (Snowflake ID)
			  - <info>Class:</info> class of the job
			  - <info>Started at:</info> start time of the job
			  - <info>Server ID:</info> server ID as defined in <options=bold>config.php</> (see `serverid`). Highlighted if it’s running on current server.
			  - <info>PID:</info> PID of process executing the job
			  - <info>Running since:</info> human readable elapsed time since job started

			EOF;

		$this
			->setName('background-job:running')
			->setDescription('Show currently running jobs')
			->setHelp($help)
			->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maximum number of results returned by the command', 200);
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$limit = (int)$input->getOption('limit');
		$jobs = $this->jobRuns->runningJobs($limit);
		$this->writeStreamingTableInOutputFormat($input, $output, $this->formatLine($jobs), 20);

		return Base::SUCCESS;
	}

	private function formatLine(iterable $jobs): \Generator {
		$now = time();
		$currentServerId = $this->serverInfo->getServerId();
		foreach ($jobs as $job) {
			yield [
				'Run ID' => $job->runId,
				'Class' => $job->className,
				'Started at' => $job->startedAt->format('Y-m-d H:i:s'),
				'Server ID' => $job->serverId === $currentServerId ? '<info>' . $job->serverId . '</info>' : $job->serverId,
				'PID' => $job->pid,
				'Running since' => $this->formatDuration($now - $job->startedAt->format('U')),
			];
		}
	}

	/**
	 * TODO Move this function to utils class with better formatting (plural, i18n…)
	 */
	private function formatDuration(int $seconds): string {
		if ($seconds < 60) {
			return sprintf('%d seconds', $seconds);
		}
		if ($seconds < 3600) {
			return sprintf('%d minutes', $seconds / 60);
		}
		if ($seconds < (3600 * 24)) {
			return sprintf('> %d hours', $seconds / 3600);
		}

		return sprintf('> %d days', $seconds / (3600 * 24));
	}
}
