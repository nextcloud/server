<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\BackgroundJob;

use ArrayIterator;
use OC\BackgroundJob\JobList;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\Job;
use OCP\Server;
use OCP\Snowflake\IGenerator;

/**
 * Class DummyJobList
 *
 * in memory job list for testing purposes
 */
class DummyJobList extends JobList {
	/**
	 * @var IJob[]
	 */
	private array $jobs = [];

	/**
	 * @var bool[]
	 */
	private array $reserved = [];

	private int $last = 0;

	public function __construct() {
	}

	/**
	 * @param IJob|class-string<IJob> $job
	 * @param mixed $argument
	 */
	public function add($job, $argument = null, ?int $firstCheck = null): void {
		if (is_string($job)) {
			/** @var IJob $job */
			$job = Server::get($job);
		}
		$job->setArgument($argument);
		$job->setId(Server::get(IGenerator::class)->nextId());
		if (!$this->has($job, null)) {
			$this->jobs[] = $job;
		}
	}

	public function scheduleAfter(string $job, int $runAfter, $argument = null): void {
		$this->add($job, $argument, $runAfter);
	}

	/**
	 * @param IJob|string $job
	 * @param mixed $argument
	 */
	public function remove($job, $argument = null): void {
		foreach ($this->jobs as $index => $listJob) {
			if (get_class($job) === get_class($listJob) && $job->getArgument() == $listJob->getArgument()) {
				unset($this->jobs[$index]);
				return;
			}
		}
	}

	public function removeById(string $id): void {
		foreach ($this->jobs as $index => $listJob) {
			if ($listJob->getId() === $id) {
				unset($this->jobs[$index]);
				return;
			}
		}
	}

	/**
	 * check if a job is in the list
	 *
	 * @param $job
	 * @param mixed $argument
	 * @return bool
	 */
	public function has($job, $argument): bool {
		return array_search($job, $this->jobs) !== false;
	}

	/**
	 * get all jobs in the list
	 *
	 * @return IJob[]
	 */
	public function getAll(): array {
		return $this->jobs;
	}

	public function getJobsIterator($job, ?int $limit, int $offset): iterable {
		if ($job instanceof IJob) {
			$jobClass = get_class($job);
		} else {
			$jobClass = $job;
		}

		$jobs = array_slice(
			array_filter(
				$this->jobs,
				fn ($job) => ($jobClass === null) || (get_class($job) === $jobClass)
			),
			$offset,
			$limit
		);

		return new ArrayIterator($jobs);
	}

	/**
	 * get the next job in the list
	 */
	public function getNext(bool $onlyTimeSensitive = false, ?array $jobClasses = null): ?IJob {
		if (count($this->jobs) > 0) {
			if ($this->last < (count($this->jobs) - 1)) {
				$i = $this->last + 1;
			} else {
				$i = 0;
			}
			return $this->jobs[$i];
		} else {
			return null;
		}
	}

	/**
	 * set the job that was last ran
	 *
	 * @param Job $job
	 */
	public function setLastJob(IJob $job): void {
		$i = array_search($job, $this->jobs);
		if ($i !== false) {
			$this->last = $i;
		} else {
			$this->last = 0;
		}
	}

	public function getById(string $id): ?IJob {
		foreach ($this->jobs as $job) {
			if ($job->getId() === $id) {
				return $job;
			}
		}
		return null;
	}

	public function getDetailsById(string $id): ?array {
		return null;
	}

	public function setLastRun(IJob $job): void {
		$job->setLastRun(time());
	}

	public function hasReservedJob(?string $className = null): bool {
		return isset($this->reserved[$className ?? '']) && $this->reserved[$className ?? ''];
	}

	public function setHasReservedJob(?string $className, bool $hasReserved): void {
		$this->reserved[$className ?? ''] = $hasReserved;
	}

	public function setExecutionTime(IJob $job, $timeTaken): void {
	}

	public function resetBackgroundJob(IJob $job): void {
	}
}
