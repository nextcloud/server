<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\BackgroundJob;

use OCP\AppFramework\QueryException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\AutoloadNotAllowedException;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\IParallelAwareJob;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use function get_class;
use function json_encode;
use function min;
use function strlen;

class JobList implements IJobList {
	/** @var array<string, int> */
	protected array $alreadyVisitedParallelBlocked = [];

	public function __construct(
		protected IDBConnection $connection,
		protected IConfig $config,
		protected ITimeFactory $timeFactory,
		protected LoggerInterface $logger,
	) {
	}

	public function add($job, $argument = null, ?int $firstCheck = null): void {
		if ($firstCheck === null) {
			$firstCheck = $this->timeFactory->getTime();
		}

		$class = ($job instanceof IJob) ? get_class($job) : $job;

		$argumentJson = json_encode($argument);
		if (strlen($argumentJson) > 4000) {
			throw new \InvalidArgumentException('Background job arguments can\'t exceed 4000 characters (json encoded)');
		}

		$query = $this->connection->getQueryBuilder();
		if (!$this->has($job, $argument)) {
			$query->insert('jobs')
				->values([
					'class' => $query->createNamedParameter($class),
					'argument' => $query->createNamedParameter($argumentJson),
					'argument_hash' => $query->createNamedParameter(hash('sha256', $argumentJson)),
					'last_run' => $query->createNamedParameter(0, IQueryBuilder::PARAM_INT),
					'last_checked' => $query->createNamedParameter($firstCheck, IQueryBuilder::PARAM_INT),
				]);
		} else {
			$query->update('jobs')
				->set('reserved_at', $query->expr()->literal(0, IQueryBuilder::PARAM_INT))
				->set('last_checked', $query->createNamedParameter($firstCheck, IQueryBuilder::PARAM_INT))
				->set('last_run', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT))
				->where($query->expr()->eq('class', $query->createNamedParameter($class)))
				->andWhere($query->expr()->eq('argument_hash', $query->createNamedParameter(hash('sha256', $argumentJson))));
		}
		$query->executeStatement();
	}

	public function scheduleAfter(string $job, int $runAfter, $argument = null): void {
		$this->add($job, $argument, $runAfter);
	}

	/**
	 * @param IJob|string $job
	 * @param mixed $argument
	 */
	public function remove($job, $argument = null): void {
		$class = ($job instanceof IJob) ? get_class($job) : $job;

		$query = $this->connection->getQueryBuilder();
		$query->delete('jobs')
			->where($query->expr()->eq('class', $query->createNamedParameter($class)));
		if (!is_null($argument)) {
			$argumentJson = json_encode($argument);
			$query->andWhere($query->expr()->eq('argument_hash', $query->createNamedParameter(hash('sha256', $argumentJson))));
		}

		// Add galera safe delete chunking if using mysql
		// Stops us hitting wsrep_max_ws_rows when large row counts are deleted
		if ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_MYSQL) {
			// Then use chunked delete
			$max = IQueryBuilder::MAX_ROW_DELETION;

			$query->setMaxResults($max);

			do {
				$deleted = $query->executeStatement();
			} while ($deleted === $max);
		} else {
			// Dont use chunked delete - let the DB handle the large row count natively
			$query->executeStatement();
		}
	}

	public function removeById(int $id): void {
		$query = $this->connection->getQueryBuilder();
		$query->delete('jobs')
			->where($query->expr()->eq('id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$query->executeStatement();
	}

	/**
	 * check if a job is in the list
	 *
	 * @param IJob|class-string<IJob> $job
	 * @param mixed $argument
	 */
	public function has($job, $argument): bool {
		$class = ($job instanceof IJob) ? get_class($job) : $job;
		$argument = json_encode($argument);

		$query = $this->connection->getQueryBuilder();
		$query->select('id')
			->from('jobs')
			->where($query->expr()->eq('class', $query->createNamedParameter($class)))
			->andWhere($query->expr()->eq('argument_hash', $query->createNamedParameter(hash('sha256', $argument))))
			->setMaxResults(1);

		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		return (bool)$row;
	}

	public function getJobs($job, ?int $limit, int $offset): array {
		$iterable = $this->getJobsIterator($job, $limit, $offset);
		return (is_array($iterable))
			? $iterable
			: iterator_to_array($iterable);
	}

	/**
	 * @param IJob|class-string<IJob>|null $job
	 * @return iterable<IJob> Avoid to store these objects as they may share a Singleton instance. You should instead use these IJobs instances while looping on the iterable.
	 */
	public function getJobsIterator($job, ?int $limit, int $offset): iterable {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('jobs')
			->setMaxResults($limit)
			->setFirstResult($offset);

		if ($job !== null) {
			$class = ($job instanceof IJob) ? get_class($job) : $job;
			$query->where($query->expr()->eq('class', $query->createNamedParameter($class)));
		}

		$result = $query->executeQuery();

		while ($row = $result->fetch()) {
			$job = $this->buildJob($row);
			if ($job) {
				yield $job;
			}
		}
		$result->closeCursor();
	}

	/**
	 * @inheritDoc
	 */
	public function getNext(bool $onlyTimeSensitive = false, ?array $jobClasses = null): ?IJob {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('jobs')
			->where($query->expr()->lte('reserved_at', $query->createNamedParameter($this->timeFactory->getTime() - 12 * 3600, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->lte('last_checked', $query->createNamedParameter($this->timeFactory->getTime(), IQueryBuilder::PARAM_INT)))
			->orderBy('last_checked', 'ASC')
			->setMaxResults(1);

		if ($onlyTimeSensitive) {
			$query->andWhere($query->expr()->eq('time_sensitive', $query->createNamedParameter(IJob::TIME_SENSITIVE, IQueryBuilder::PARAM_INT)));
		}

		if (!empty($jobClasses)) {
			$orClasses = [];
			foreach ($jobClasses as $jobClass) {
				$orClasses[] = $query->expr()->eq('class', $query->createNamedParameter($jobClass, IQueryBuilder::PARAM_STR));
			}
			$query->andWhere($query->expr()->orX(...$orClasses));
		}

		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		if ($row) {
			$job = $this->buildJob($row);

			if ($job instanceof IParallelAwareJob && !$job->getAllowParallelRuns() && $this->hasReservedJob(get_class($job))) {
				if (!isset($this->alreadyVisitedParallelBlocked[get_class($job)])) {
					$this->alreadyVisitedParallelBlocked[get_class($job)] = $job->getId();
				} elseif ($this->alreadyVisitedParallelBlocked[get_class($job)] === $job->getId()) {
					$this->logger->info('Skipped through all jobs and revisited a IParallelAwareJob blocked job again, giving up.', ['app' => 'cron']);
					return null;
				}
				$this->logger->info('Skipping ' . get_class($job) . ' job with ID ' . $job->getId() . ' because another job with the same class is already running', ['app' => 'cron']);

				$update = $this->connection->getQueryBuilder();
				$update->update('jobs')
					->set('last_checked', $update->createNamedParameter($this->timeFactory->getTime() + 1))
					->where($update->expr()->eq('id', $update->createParameter('jobid')));
				$update->setParameter('jobid', $row['id']);
				$update->executeStatement();

				return $this->getNext($onlyTimeSensitive, $jobClasses);
			}

			if ($job !== null && isset($this->alreadyVisitedParallelBlocked[get_class($job)])) {
				unset($this->alreadyVisitedParallelBlocked[get_class($job)]);
			}

			if ($job instanceof \OCP\BackgroundJob\TimedJob) {
				$now = $this->timeFactory->getTime();
				$nextPossibleRun = $job->getLastRun() + $job->getInterval();
				if ($now < $nextPossibleRun) {
					// This job is not ready for execution yet. Set timestamps to the future to avoid
					// re-checking with every cron run.
					// To avoid bugs that lead to jobs never executing again, the future timestamp is
					// capped at two days.
					$nextCheck = min($nextPossibleRun, $now + 48 * 3600);
					$updateTimedJob = $this->connection->getQueryBuilder();
					$updateTimedJob->update('jobs')
						->set('last_checked', $updateTimedJob->createNamedParameter($nextCheck, IQueryBuilder::PARAM_INT))
						->where($updateTimedJob->expr()->eq('id', $updateTimedJob->createParameter('jobid')));
					$updateTimedJob->setParameter('jobid', $row['id']);
					$updateTimedJob->executeStatement();

					return $this->getNext($onlyTimeSensitive, $jobClasses);
				}
			}

			$update = $this->connection->getQueryBuilder();
			$update->update('jobs')
				->set('reserved_at', $update->createNamedParameter($this->timeFactory->getTime()))
				->set('last_checked', $update->createNamedParameter($this->timeFactory->getTime()))
				->where($update->expr()->eq('id', $update->createParameter('jobid')))
				->andWhere($update->expr()->eq('reserved_at', $update->createParameter('reserved_at')))
				->andWhere($update->expr()->eq('last_checked', $update->createParameter('last_checked')));
			$update->setParameter('jobid', $row['id']);
			$update->setParameter('reserved_at', $row['reserved_at']);
			$update->setParameter('last_checked', $row['last_checked']);
			$count = $update->executeStatement();

			if ($count === 0) {
				// Background job already executed elsewhere, try again.
				return $this->getNext($onlyTimeSensitive, $jobClasses);
			}

			if ($job === null) {
				// set the last_checked to 12h in the future to not check failing jobs all over again
				$reset = $this->connection->getQueryBuilder();
				$reset->update('jobs')
					->set('reserved_at', $reset->expr()->literal(0, IQueryBuilder::PARAM_INT))
					->set('last_checked', $reset->createNamedParameter($this->timeFactory->getTime() + 12 * 3600, IQueryBuilder::PARAM_INT))
					->where($reset->expr()->eq('id', $reset->createNamedParameter($row['id'], IQueryBuilder::PARAM_INT)));
				$reset->executeStatement();

				// Background job from disabled app, try again.
				return $this->getNext($onlyTimeSensitive, $jobClasses);
			}

			return $job;
		} else {
			return null;
		}
	}

	/**
	 * @return ?IJob The job matching the id. Beware that this object may be a singleton and may be modified by the next call to buildJob.
	 */
	public function getById(int $id): ?IJob {
		$row = $this->getDetailsById($id);

		if ($row) {
			return $this->buildJob($row);
		}

		return null;
	}

	public function getDetailsById(int $id): ?array {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('jobs')
			->where($query->expr()->eq('id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		if ($row) {
			return $row;
		}

		return null;
	}

	/**
	 * get the job object from a row in the db
	 *
	 * @param array{class:class-string<IJob>, id:mixed, last_run:mixed, argument:string} $row
	 * @return ?IJob the next job to run. Beware that this object may be a singleton and may be modified by the next call to buildJob.
	 */
	private function buildJob(array $row): ?IJob {
		try {
			try {
				// Try to load the job as a service
				/** @var IJob $job */
				$job = \OCP\Server::get($row['class']);
			} catch (QueryException $e) {
				if (class_exists($row['class'])) {
					$class = $row['class'];
					$job = new $class();
				} else {
					$this->logger->warning('failed to create instance of background job: ' . $row['class'], ['app' => 'cron', 'exception' => $e]);
					// Remove job from disabled app or old version of an app
					$this->removeById($row['id']);
					return null;
				}
			}

			if (!($job instanceof IJob)) {
				// This most likely means an invalid job was enqueued. We can ignore it.
				return null;
			}
			$job->setId((int)$row['id']);
			$job->setLastRun((int)$row['last_run']);
			$job->setArgument(json_decode($row['argument'], true));
			return $job;
		} catch (AutoloadNotAllowedException $e) {
			// job is from a disabled app, ignore
			return null;
		}
	}

	/**
	 * set the job that was last ran
	 */
	public function setLastJob(IJob $job): void {
		$this->unlockJob($job);
		$this->config->setAppValue('backgroundjob', 'lastjob', (string)$job->getId());
	}

	/**
	 * Remove the reservation for a job
	 */
	public function unlockJob(IJob $job): void {
		$query = $this->connection->getQueryBuilder();
		$query->update('jobs')
			->set('reserved_at', $query->expr()->literal(0, IQueryBuilder::PARAM_INT))
			->where($query->expr()->eq('id', $query->createNamedParameter($job->getId(), IQueryBuilder::PARAM_INT)));
		$query->executeStatement();
	}

	/**
	 * set the lastRun of $job to now
	 */
	public function setLastRun(IJob $job): void {
		$query = $this->connection->getQueryBuilder();
		$query->update('jobs')
			->set('last_run', $query->createNamedParameter(time(), IQueryBuilder::PARAM_INT))
			->where($query->expr()->eq('id', $query->createNamedParameter($job->getId(), IQueryBuilder::PARAM_INT)));

		if ($job instanceof \OCP\BackgroundJob\TimedJob
			&& !$job->isTimeSensitive()) {
			$query->set('time_sensitive', $query->createNamedParameter(IJob::TIME_INSENSITIVE));
		}

		$query->executeStatement();
	}

	/**
	 * @param int $timeTaken
	 */
	public function setExecutionTime(IJob $job, $timeTaken): void {
		$query = $this->connection->getQueryBuilder();
		$query->update('jobs')
			->set('execution_duration', $query->createNamedParameter($timeTaken, IQueryBuilder::PARAM_INT))
			->set('reserved_at', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT))
			->where($query->expr()->eq('id', $query->createNamedParameter($job->getId(), IQueryBuilder::PARAM_INT)));
		$query->executeStatement();
	}

	/**
	 * Reset the $job so it executes on the next trigger
	 *
	 * @since 23.0.0
	 */
	public function resetBackgroundJob(IJob $job): void {
		$query = $this->connection->getQueryBuilder();
		$query->update('jobs')
			->set('last_run', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT))
			->set('reserved_at', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT))
			->where($query->expr()->eq('id', $query->createNamedParameter($job->getId()), IQueryBuilder::PARAM_INT));
		$query->executeStatement();
	}

	public function hasReservedJob(?string $className = null): bool {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('jobs')
			->where($query->expr()->gt('reserved_at', $query->createNamedParameter($this->timeFactory->getTime() - 6 * 3600, IQueryBuilder::PARAM_INT)))
			->setMaxResults(1);

		if ($className !== null) {
			$query->andWhere($query->expr()->eq('class', $query->createNamedParameter($className)));
		}

		try {
			$result = $query->executeQuery();
			$hasReservedJobs = $result->fetch() !== false;
			$result->closeCursor();
			return $hasReservedJobs;
		} catch (Exception $e) {
			$this->logger->debug('Querying reserved jobs failed', ['exception' => $e]);
			return false;
		}
	}

	public function countByClass(): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('class')
			->selectAlias($query->func()->count('id'), 'count')
			->from('jobs')
			->orderBy('count')
			->groupBy('class');

		$result = $query->executeQuery();

		$jobs = [];

		while (($row = $result->fetch()) !== false) {
			/**
			 * @var array{count:int, class:class-string} $row
			 */
			$jobs[] = $row;
		}

		return $jobs;
	}
}
