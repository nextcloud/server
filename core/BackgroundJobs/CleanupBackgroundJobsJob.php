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
use Psr\Log\LoggerInterface;
use RuntimeException;

class CleanupBackgroundJobsJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private readonly JobRuns $jobRuns,
		private readonly IServerInfo $serverInfo,
		private readonly IConfig $config,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setInterval(60 * 60);
		$this->setTimeSensitivity(IJob::TIME_SENSITIVE);
	}

	#[Override]
	protected function run($argument): void {
		$this->reapCrashedJobs();
		$this->cleanOldestRuns();
	}

	private function reapCrashedJobs(): void {
		$currentServerId = $this->serverInfo->getServerId();

		foreach ($this->jobRuns->runningJobs(1000) as $job) {
			if ($job->serverId !== $currentServerId) {
				continue;
			}
			$output = [];
			$result = 0;
			exec('ps -p ' . escapeshellarg((string)$job->pid) . ' -o cmd', $output, $result);
			if (count($output) === 1 && current($output) === 'CMD' && $result === 1) {
				// Process doesn't exists anymore
				$maxDuration = (new DateTimeImmutable())->diff($job->startedAt);
				$maxDuration
					= ($maxDuration->days * 24 * 60 * 60 * 1000)
					+ ($maxDuration->h * 60 * 60 * 1000)
					+ ($maxDuration->i * 60 * 1000)
					+ ($maxDuration->s * 1000)
					+ (int)($maxDuration->f * 1000);
				$this->jobRuns->finished($job->runId, $maxDuration, 0, JobStatus::CRASHED);
				$this->logger->warning('No process matching PID {pid} found on server {serverId}. Job {runId} was marked as crashed', [
					'pid' => $job->pid,
					'serverId' => $job->serverId,
					'runId' => $job->runId,
				]);
			}
		}
	}

	private function cleanOldestRuns(): void {
		$daysToKeep = $this->config->getSystemValueInt('background_jobs_expiration_days', 60);
		if ($daysToKeep < 1) {
			throw new RuntimeException('Invalid number of days');
		}
		$cleanBeforeTimestamp = time() - ($daysToKeep * 24 * 3600);

		$cleanedJobs = $this->jobRuns->deleteBefore($cleanBeforeTimestamp);
		if ($cleanedJobs > 0) {
			$this->logger->info(
				'Cleanup of old background jobs. Number of jobs removed: ' . $cleanedJobs . 'Reason: older than ' . $daysToKeep . ' days.',
			);
		}
	}
}
