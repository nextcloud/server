<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use DateTimeImmutable;
use OC\BackgroundJob\JobRuns;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\JobStatus;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use OCP\IServerInfo;
use Override;

class CleanupBackgroundJobsJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private readonly JobRuns $jobRuns,
		private readonly IServerInfo $serverInfo,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setInterval(60 * 60);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
	}

	#[Override]
	protected function run($argument): void {
		$this->reapCrashedJobs();

		// TODO Clean oldest jobs
	}

	private function reapCrashedJobs(): void {
		$currentServerId = $this->serverInfo->getServerId();

		foreach ($this->jobRuns->runningJobs(1000) as $job) {
			if ($job->serverId !== $currentServerId) {
				continue;
			}
			exec('ps -p ' . escapeshellarg((string)$job->pid) . ' -o cmd', $output, $result);
			if (count($output) === 1 && $output[0] === 'CMD' && $result === 1) {
				// Process doesn't exists anymore
				$maxDuration = (new DateTimeImmutable())->diff($job->startedAt);
				$maxDuration =
					($maxDuration->format('%a') * 24 * 60 * 60 * 1000)
					+ ($maxDuration->format('%h') * 60 * 60 * 1000)
					+ ($maxDuration->format('%m') * 60 * 1000)
					+ ($maxDuration->format('%s') * 1000)
					+ (int)($maxDuration->format('%f') / 1000);
				$this->jobRuns->finished($job->runId, $maxDuration, 0, JobStatus::CRASHED);
			}
		}
	}
}
