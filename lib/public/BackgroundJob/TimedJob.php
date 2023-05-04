<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\BackgroundJob;

use OC\BackgroundJob\JobList;
use OCP\ILogger;

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
		if ($sensitivity !== IJob::TIME_SENSITIVE &&
			$sensitivity !== IJob::TIME_INSENSITIVE) {
			throw new \InvalidArgumentException('Invalid sensitivity');
		}

		$this->timeSensitivity = $sensitivity;
	}

	/**
	 * run the job if the last run is is more than the interval ago
	 *
	 * @param JobList $jobList
	 * @param ILogger|null $logger
	 *
	 * @since 15.0.0
	 * @deprecated since 25.0.0 Use start() instead
	 */
	final public function execute($jobList, ILogger $logger = null) {
		$this->start($jobList);
	}

	/**
	 * Run the job if the last run is is more than the interval ago
	 *
	 * @since 25.0.0
	 */
	final public function start(IJobList $jobList): void {
		if (($this->time->getTime() - $this->lastRun) > $this->interval) {
			parent::start($jobList);
		}
	}
}
