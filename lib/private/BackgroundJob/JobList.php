<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Noveen Sachdeva <noveen.sachdeva@research.iiit.ac.in>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\BackgroundJob;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use OCP\AppFramework\QueryException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\AutoloadNotAllowedException;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\IJobList;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;

class JobList implements IJobList {
	protected IDBConnection $connection;
	protected IConfig $config;
	protected ITimeFactory $timeFactory;

	public function __construct(IDBConnection $connection, IConfig $config, ITimeFactory $timeFactory) {
		$this->connection = $connection;
		$this->config = $config;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param IJob|class-string<IJob> $job
	 * @param mixed $argument
	 */
	public function add($job, $argument = null): void {
		if ($job instanceof IJob) {
			$class = get_class($job);
		} else {
			$class = $job;
		}

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
					'argument_hash' => $query->createNamedParameter(md5($argumentJson)),
					'last_run' => $query->createNamedParameter(0, IQueryBuilder::PARAM_INT),
					'last_checked' => $query->createNamedParameter($this->timeFactory->getTime(), IQueryBuilder::PARAM_INT),
				]);
		} else {
			$query->update('jobs')
				->set('reserved_at', $query->expr()->literal(0, IQueryBuilder::PARAM_INT))
				->set('last_checked', $query->createNamedParameter($this->timeFactory->getTime(), IQueryBuilder::PARAM_INT))
				->where($query->expr()->eq('class', $query->createNamedParameter($class)))
				->andWhere($query->expr()->eq('argument_hash', $query->createNamedParameter(md5($argumentJson))));
		}
		$query->executeStatement();
	}

	/**
	 * @param IJob|string $job
	 * @param mixed $argument
	 */
	public function remove($job, $argument = null): void {
		if ($job instanceof IJob) {
			$class = get_class($job);
		} else {
			$class = $job;
		}

		$query = $this->connection->getQueryBuilder();
		$query->delete('jobs')
			->where($query->expr()->eq('class', $query->createNamedParameter($class)));
		if (!is_null($argument)) {
			$argumentJson = json_encode($argument);
			$query->andWhere($query->expr()->eq('argument_hash', $query->createNamedParameter(md5($argumentJson))));
		}

		// Add galera safe delete chunking if using mysql
		// Stops us hitting wsrep_max_ws_rows when large row counts are deleted
		if ($this->connection->getDatabasePlatform() instanceof MySQLPlatform) {
			// Then use chunked delete
			$max = IQueryBuilder::MAX_ROW_DELETION;

			$query->setMaxResults($max);

			do {
				$deleted = $query->execute();
			} while ($deleted === $max);
		} else {
			// Dont use chunked delete - let the DB handle the large row count natively
			$query->execute();
		}
	}

	protected function removeById(int $id): void {
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
		if ($job instanceof IJob) {
			$class = get_class($job);
		} else {
			$class = $job;
		}
		$argument = json_encode($argument);

		$query = $this->connection->getQueryBuilder();
		$query->select('id')
			->from('jobs')
			->where($query->expr()->eq('class', $query->createNamedParameter($class)))
			->andWhere($query->expr()->eq('argument_hash', $query->createNamedParameter(md5($argument))))
			->setMaxResults(1);

		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		return (bool) $row;
	}

	public function getJobs($job, ?int $limit, int $offset): array {
		$iterable = $this->getJobsIterator($job, $limit, $offset);
		if (is_array($iterable)) {
			return $iterable;
		} else {
			return iterator_to_array($iterable);
		}
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
			if ($job instanceof IJob) {
				$class = get_class($job);
			} else {
				$class = $job;
			}
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
	 * Get the next job in the list
	 * @return ?IJob the next job to run. Beware that this object may be a singleton and may be modified by the next call to buildJob.
	 */
	public function getNext(bool $onlyTimeSensitive = false): ?IJob {
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

		$update = $this->connection->getQueryBuilder();
		$update->update('jobs')
			->set('reserved_at', $update->createNamedParameter($this->timeFactory->getTime()))
			->set('last_checked', $update->createNamedParameter($this->timeFactory->getTime()))
			->where($update->expr()->eq('id', $update->createParameter('jobid')))
			->andWhere($update->expr()->eq('reserved_at', $update->createParameter('reserved_at')))
			->andWhere($update->expr()->eq('last_checked', $update->createParameter('last_checked')));

		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		if ($row) {
			$update->setParameter('jobid', $row['id']);
			$update->setParameter('reserved_at', $row['reserved_at']);
			$update->setParameter('last_checked', $row['last_checked']);
			$count = $update->executeStatement();

			if ($count === 0) {
				// Background job already executed elsewhere, try again.
				return $this->getNext($onlyTimeSensitive);
			}
			$job = $this->buildJob($row);

			if ($job === null) {
				// set the last_checked to 12h in the future to not check failing jobs all over again
				$reset = $this->connection->getQueryBuilder();
				$reset->update('jobs')
					->set('reserved_at', $reset->expr()->literal(0, IQueryBuilder::PARAM_INT))
					->set('last_checked', $reset->createNamedParameter($this->timeFactory->getTime() + 12 * 3600, IQueryBuilder::PARAM_INT))
					->where($reset->expr()->eq('id', $reset->createNamedParameter($row['id'], IQueryBuilder::PARAM_INT)));
				$reset->executeStatement();

				// Background job from disabled app, try again.
				return $this->getNext($onlyTimeSensitive);
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
					// job from disabled app or old version of an app, no need to do anything
					return null;
				}
			}

			if (!($job instanceof IJob)) {
				// This most likely means an invalid job was enqueued. We can ignore it.
				return null;
			}
			$job->setId((int) $row['id']);
			$job->setLastRun((int) $row['last_run']);
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
}
