<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use Override;
use Psr\Log\LoggerInterface;

/**
 * Base class for background jobs
 *
 * This is here if you want to do advanced stuff in your background jobs.
 * For the most common use cases have a look at QueuedJob and TimedJob
 *
 * @since 15.0.0
 * @since 25.0.0 deprecated `execute()` method in favor of `start()`
 * @since 33.0.0 removed deprecated `execute()` method
 */
abstract class Job implements IJob, IParallelAwareJob {
	protected string $id = '0';
	protected int $lastRun = 0;
	protected mixed $argument = null;
	protected bool $allowParallelRuns = true;

	/**
	 * @since 15.0.0
	 */
	public function __construct(
		protected ITimeFactory $time,
	) {
	}

	#[Override]
	public function start(IJobList $jobList): void {
		$jobList->setLastRun($this);
		$logger = \OCP\Server::get(LoggerInterface::class);

		try {
			$jobDetails = get_class($this) . ' (id: ' . $this->getId() . ', arguments: ' . json_encode($this->getArgument()) . ')';
			$jobStartTime = $this->time->getTime();
			$logger->debug('Starting job ' . $jobDetails, ['app' => 'cron']);
			$this->run($this->argument);
			$timeTaken = $this->time->getTime() - $jobStartTime;

			$logger->debug('Finished job ' . $jobDetails . ' in ' . $timeTaken . ' seconds', ['app' => 'cron']);
			$jobList->setExecutionTime($this, $timeTaken);
		} catch (\Throwable $e) {
			if ($logger) {
				$logger->error('Error while running background job ' . $jobDetails, [
					'app' => 'core',
					'exception' => $e,
				]);
			}
		}
	}

	#[Override]
	final public function setId(string $id): void {
		$this->id = $id;
	}

	#[Override]
	final public function setLastRun(int $lastRun): void {
		$this->lastRun = $lastRun;
	}

	#[Override]
	public function setArgument(mixed $argument): void {
		$this->argument = $argument;
	}

	#[Override]
	final public function getId(): string {
		return $this->id;
	}

	#[Override]
	final public function getLastRun(): int {
		return $this->lastRun;
	}

	#[Override]
	public function getArgument(): mixed {
		return $this->argument;
	}

	#[Override]
	public function setAllowParallelRuns(bool $allow): void {
		$this->allowParallelRuns = $allow;
	}

	#[Override]
	public function getAllowParallelRuns(): bool {
		return $this->allowParallelRuns;
	}

	abstract protected function run($argument);
}
