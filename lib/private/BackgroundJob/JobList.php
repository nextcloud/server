<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\BackgroundJob;

use OCP\AppFramework\QueryException;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\IJobList;
use OCP\AutoloadNotAllowedException;
use OCP\DB\QueryBuilder\IQueryBuilder;

class JobList implements IJobList {
	/** @var \OCP\IDBConnection */
	protected $connection;

	/**
	 * @var \OCP\IConfig $config
	 */
	protected $config;

	/**
	 * @param \OCP\IDBConnection $connection
	 * @param \OCP\IConfig $config
	 */
	public function __construct($connection, $config) {
		$this->connection = $connection;
		$this->config = $config;
	}

	/**
	 * @param IJob|string $job
	 * @param mixed $argument
	 */
	public function add($job, $argument = null) {
		if (!$this->has($job, $argument)) {
			if ($job instanceof IJob) {
				$class = get_class($job);
			} else {
				$class = $job;
			}

			$argument = json_encode($argument);
			if (strlen($argument) > 4000) {
				throw new \InvalidArgumentException('Background job arguments can\'t exceed 4000 characters (json encoded)');
			}

			$query = $this->connection->getQueryBuilder();
			$query->insert('jobs')
				->values([
					'class' => $query->createNamedParameter($class),
					'argument' => $query->createNamedParameter($argument),
					'last_run' => $query->createNamedParameter(0, IQueryBuilder::PARAM_INT),
				]);
			$query->execute();
		}
	}

	/**
	 * @param IJob|string $job
	 * @param mixed $argument
	 */
	public function remove($job, $argument = null) {
		if ($job instanceof IJob) {
			$class = get_class($job);
		} else {
			$class = $job;
		}

		$query = $this->connection->getQueryBuilder();
		$query->delete('jobs')
			->where($query->expr()->eq('class', $query->createNamedParameter($class)));
		if (!is_null($argument)) {
			$argument = json_encode($argument);
			$query->andWhere($query->expr()->eq('argument', $query->createNamedParameter($argument)));
		}
		$query->execute();
	}

	/**
	 * @param int $id
	 */
	protected function removeById($id) {
		$query = $this->connection->getQueryBuilder();
		$query->delete('jobs')
			->where($query->expr()->eq('id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$query->execute();
	}

	/**
	 * check if a job is in the list
	 *
	 * @param IJob|string $job
	 * @param mixed $argument
	 * @return bool
	 */
	public function has($job, $argument) {
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
			->andWhere($query->expr()->eq('argument', $query->createNamedParameter($argument)))
			->setMaxResults(1);

		$result = $query->execute();
		$row = $result->fetch();
		$result->closeCursor();

		return (bool) $row;
	}

	/**
	 * get all jobs in the list
	 *
	 * @return IJob[]
	 * @deprecated 9.0.0 - This method is dangerous since it can cause load and
	 * memory problems when creating too many instances.
	 */
	public function getAll() {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('jobs');
		$result = $query->execute();

		$jobs = [];
		while ($row = $result->fetch()) {
			$job = $this->buildJob($row);
			if ($job) {
				$jobs[] = $job;
			}
		}
		$result->closeCursor();

		return $jobs;
	}

	/**
	 * get the next job in the list
	 *
	 * @return IJob|null
	 */
	public function getNext() {
		$lastId = $this->getLastJob();

		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('jobs')
			->where($query->expr()->lt('id', $query->createNamedParameter($lastId, IQueryBuilder::PARAM_INT)))
			->orderBy('id', 'DESC')
			->setMaxResults(1);
		$result = $query->execute();
		$row = $result->fetch();
		$result->closeCursor();

		if ($row) {
			$jobId = $row['id'];
			$job = $this->buildJob($row);
		} else {
			//begin at the start of the queue
			$query = $this->connection->getQueryBuilder();
			$query->select('*')
				->from('jobs')
				->orderBy('id', 'DESC')
				->setMaxResults(1);
			$result = $query->execute();
			$row = $result->fetch();
			$result->closeCursor();

			if ($row) {
				$jobId = $row['id'];
				$job = $this->buildJob($row);
			} else {
				return null; //empty job list
			}
		}

		if (is_null($job)) {
			$this->removeById($jobId);
			return $this->getNext();
		} else {
			return $job;
		}
	}

	/**
	 * @param int $id
	 * @return IJob|null
	 */
	public function getById($id) {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('jobs')
			->where($query->expr()->eq('id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$result = $query->execute();
		$row = $result->fetch();
		$result->closeCursor();

		if ($row) {
			return $this->buildJob($row);
		} else {
			return null;
		}
	}

	/**
	 * get the job object from a row in the db
	 *
	 * @param array $row
	 * @return IJob|null
	 */
	private function buildJob($row) {
		try {
			try {
				// Try to load the job as a service
				/** @var IJob $job */
				$job = \OC::$server->query($row['class']);
			} catch (QueryException $e) {
				if (class_exists($row['class'])) {
					$class = $row['class'];
					$job = new $class();
				} else {
					// job from disabled app or old version of an app, no need to do anything
					return null;
				}
			}

			$job->setId($row['id']);
			$job->setLastRun($row['last_run']);
			$job->setArgument(json_decode($row['argument'], true));
			return $job;
		} catch (AutoloadNotAllowedException $e) {
			// job is from a disabled app, ignore
			return null;
		}
	}

	/**
	 * set the job that was last ran
	 *
	 * @param IJob $job
	 */
	public function setLastJob($job) {
		$this->config->setAppValue('backgroundjob', 'lastjob', $job->getId());
	}

	/**
	 * get the id of the last ran job
	 *
	 * @return int
	 */
	public function getLastJob() {
		return (int) $this->config->getAppValue('backgroundjob', 'lastjob', 0);
	}

	/**
	 * set the lastRun of $job to now
	 *
	 * @param IJob $job
	 */
	public function setLastRun($job) {
		$query = $this->connection->getQueryBuilder();
		$query->update('jobs')
			->set('last_run', $query->createNamedParameter(time(), IQueryBuilder::PARAM_INT))
			->where($query->expr()->eq('id', $query->createNamedParameter($job->getId(), IQueryBuilder::PARAM_INT)));
		$query->execute();
	}
}
