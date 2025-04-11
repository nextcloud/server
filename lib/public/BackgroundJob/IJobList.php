<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\BackgroundJob;

/**
 * Interface IJobList
 *
 * This interface provides functions to register background jobs
 *
 * To create a new background job create a new class that inherits from either
 * \OCP\BackgroundJob\Job, \OCP\BackgroundJob\QueuedJob or
 * \OCP\BackgroundJob\TimedJob and register it using ->add($job, $argument),
 * $argument will be passed to the run() function of the job when the job is
 * executed.
 *
 * A regular job will be executed every time cron.php is run, a QueuedJob will
 * only run once and a TimedJob will only run at a specific interval which is to
 * be specified in the constructor of the job by calling
 * $this->setInterval($interval) with $interval in seconds.
 *
 * This interface should be used directly and not implemented by an application.
 * The implementation is provided by the server.
 *
 * @since 7.0.0
 */
interface IJobList {
	/**
	 * Add a job to the list
	 *
	 * @param IJob|class-string<IJob> $job
	 * @param mixed $argument The argument to be passed to $job->run() when the job is executed
	 * @since 7.0.0
	 */
	public function add($job, $argument = null): void;

	/**
	 * Add a job to the list but only run it after the given timestamp
	 *
	 * For cron background jobs this means the job will likely run shortly after the timestamp
	 * has been reached. For ajax background jobs the job might only run when users are active
	 * on the instance again.
	 *
	 * @param class-string<IJob> $job
	 * @param mixed $argument The serializable argument to be passed to $job->run() when the job is executed
	 * @since 28.0.0
	 */
	public function scheduleAfter(string $job, int $runAfter, $argument = null): void;

	/**
	 * Remove a job from the list
	 *
	 * @param IJob|class-string<IJob> $job
	 * @param mixed $argument
	 * @since 7.0.0
	 */
	public function remove($job, $argument = null): void;

	/**
	 * Remove a job from the list by id
	 *
	 * @param int $id
	 * @since 30.0.0
	 */
	public function removeById(int $id): void;

	/**
	 * check if a job is in the list
	 *
	 * @param IJob|class-string<IJob> $job
	 * @param mixed $argument
	 * @since 7.0.0
	 */
	public function has($job, $argument): bool;

	/**
	 * Get jobs matching the search
	 *
	 * @param IJob|class-string<IJob>|null $job
	 * @return array<IJob>
	 * @since 25.0.0
	 * @deprecated 26.0.0 Use getJobsIterator instead to avoid duplicated job objects
	 */
	public function getJobs($job, ?int $limit, int $offset): array;

	/**
	 * Get jobs matching the search
	 *
	 * @param IJob|class-string<IJob>|null $job
	 * @return iterable<IJob>
	 * @since 26.0.0
	 */
	public function getJobsIterator($job, ?int $limit, int $offset): iterable;

	/**
	 * Get the next job in the list
	 *
	 * @param bool $onlyTimeSensitive Whether we get only time sensitive jobs or not
	 * @param class-string<IJob>[]|null $jobClasses List of job classes to restrict which next job we get
	 * @return ?IJob the next job to run. Beware that this object may be a singleton and may be modified by the next call to buildJob.
	 * @since 7.0.0 - In 24.0.0 parameter $onlyTimeSensitive got added; In 30.0.0 parameter $jobClasses got added
	 */
	public function getNext(bool $onlyTimeSensitive = false, ?array $jobClasses = null): ?IJob;

	/**
	 * @since 7.0.0
	 */
	public function getById(int $id): ?IJob;

	/**
	 * @since 23.0.0
	 */
	public function getDetailsById(int $id): ?array;

	/**
	 * set the job that was last ran to the current time
	 *
	 * @since 7.0.0
	 */
	public function setLastJob(IJob $job): void;

	/**
	 * Remove the reservation for a job
	 *
	 * @since 9.1.0
	 */
	public function unlockJob(IJob $job): void;

	/**
	 * set the lastRun of $job to now
	 *
	 * @since 7.0.0
	 */
	public function setLastRun(IJob $job): void;

	/**
	 * set the run duration of $job
	 *
	 * @since 12.0.0
	 */
	public function setExecutionTime(IJob $job, int $timeTaken): void;

	/**
	 * Reset the $job so it executes on the next trigger
	 *
	 * @since 23.0.0
	 */
	public function resetBackgroundJob(IJob $job): void;

	/**
	 * Checks whether a job of the passed class was reserved to run
	 * in the last 6h
	 *
	 * @param string|null $className
	 * @return bool
	 * @since 27.0.0
	 */
	public function hasReservedJob(?string $className): bool;

	/**
	 * Returns a count of jobs per Job class
	 *
	 * @return list<array{class:class-string, count:int}>
	 * @since 30.0.0
	 */
	public function countByClass(): array;
}
