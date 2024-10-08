<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\BackgroundJob;

use OCP\ILogger;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Simple base class to extend to run periodic background jobs.
 * Call setInterval with your desired interval in seconds from the constructor.
 *
 * @since 15.0.0
 */
abstract class TimedJob extends Job {
	protected int $interval = 0;
	protected int $timeSensitivity = IJob::TIME_SENSITIVE;

	/**
	 * Set the interval for the job
	 *
	 * @param int $seconds the time to pass between two runs of the same job in seconds
	 *
	 * @since 15.0.0
	 */
	public function setInterval(int $seconds) {
		$this->interval = $seconds;
	}

	/**
	 * Whether the background job is time sensitive and needs to run soon after
	 * the scheduled interval, of if it is okay to be delayed until a later time.
	 *
	 * @return bool
	 * @since 24.0.0
	 */
	public function isTimeSensitive(): bool {
		return $this->timeSensitivity === IJob::TIME_SENSITIVE;
	}

	/**
	 * If your background job is not time sensitive (sending instant email
	 * notifications, etc.) it would be nice to set it to IJob::TIME_INSENSITIVE
	 * This way the execution can be delayed during high usage times.
	 *
	 * @param int $sensitivity
	 * @psalm-param IJob::TIME_* $sensitivity
	 * @return void
	 * @since 24.0.0
	 */
	public function setTimeSensitivity(int $sensitivity): void {
		if ($sensitivity !== self::TIME_SENSITIVE &&
			$sensitivity !== self::TIME_INSENSITIVE) {
			throw new \InvalidArgumentException('Invalid sensitivity');
		}

		$this->timeSensitivity = $sensitivity;
	}

	/**
	 * Run the job if the last run is more than the interval ago
	 *
	 * @param IJobList $jobList
	 * @param ILogger|null $logger
	 *
	 * @since 15.0.0
	 * @deprecated 25.0.0 Use start() instead
	 */
	final public function execute(IJobList $jobList, ?ILogger $logger = null) {
		$this->start($jobList);
	}

	/**
	 * Run the job if the last run is more than the interval ago
	 *
	 * @since 25.0.0
	 */
	final public function start(IJobList $jobList): void {
		if (($this->time->getTime() - $this->lastRun) > $this->interval) {
			if ($this->interval >= 12 * 60 * 60 && $this->isTimeSensitive()) {
				Server::get(LoggerInterface::class)->debug('TimedJob ' . get_class($this) . ' has a configured interval of ' . $this->interval . ' seconds, but is also marked as time sensitive. Please consider marking it as time insensitive to allow more sensitive jobs to run when needed.');
			}
			parent::start($jobList);
		}
	}
}
