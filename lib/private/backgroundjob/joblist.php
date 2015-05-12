<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

use OCP\BackgroundJob\IJobList;

class JobList implements IJobList {
	/**
	 * @var \OCP\IDBConnection
	 */
	private $conn;

	/**
	 * @var \OCP\IConfig $config
	 */
	private $config;

	/**
	 * @param \OCP\IDBConnection $conn
	 * @param \OCP\IConfig $config
	 */
	public function __construct($conn, $config) {
		$this->conn = $conn;
		$this->config = $config;
	}

	/**
	 * @param Job|string $job
	 * @param mixed $argument
	 */
	public function add($job, $argument = null) {
		if (!$this->has($job, $argument)) {
			if ($job instanceof Job) {
				$class = get_class($job);
			} else {
				$class = $job;
			}
			$argument = json_encode($argument);
			if (strlen($argument) > 4000) {
				throw new \InvalidArgumentException('Background job arguments can\'t exceed 4000 characters (json encoded)');
			}
			$query = $this->conn->prepare('INSERT INTO `*PREFIX*jobs`(`class`, `argument`, `last_run`) VALUES(?, ?, 0)');
			$query->execute(array($class, $argument));
		}
	}

	/**
	 * @param Job|string $job
	 * @param mixed $argument
	 */
	public function remove($job, $argument = null) {
		if ($job instanceof Job) {
			$class = get_class($job);
		} else {
			$class = $job;
		}
		if (!is_null($argument)) {
			$argument = json_encode($argument);
			$query = $this->conn->prepare('DELETE FROM `*PREFIX*jobs` WHERE `class` = ? AND `argument` = ?');
			$query->execute(array($class, $argument));
		} else {
			$query = $this->conn->prepare('DELETE FROM `*PREFIX*jobs` WHERE `class` = ?');
			$query->execute(array($class));
		}
	}

	/**
	 * check if a job is in the list
	 *
	 * @param Job|string $job
	 * @param mixed $argument
	 * @return bool
	 */
	public function has($job, $argument) {
		if ($job instanceof Job) {
			$class = get_class($job);
		} else {
			$class = $job;
		}
		$argument = json_encode($argument);
		$query = $this->conn->prepare('SELECT `id` FROM `*PREFIX*jobs` WHERE `class` = ? AND `argument` = ?');
		$query->execute(array($class, $argument));
		return (bool)$query->fetch();
	}

	/**
	 * get all jobs in the list
	 *
	 * @return Job[]
	 */
	public function getAll() {
		$query = $this->conn->prepare('SELECT `id`, `class`, `last_run`, `argument` FROM `*PREFIX*jobs`');
		$query->execute();
		$jobs = array();
		while ($row = $query->fetch()) {
			$job = $this->buildJob($row);
			if ($job) {
				$jobs[] = $job;
			}
		}
		return $jobs;
	}

	/**
	 * get the next job in the list
	 *
	 * @return Job
	 */
	public function getNext() {
		$lastId = $this->getLastJob();
		$query = $this->conn->prepare('SELECT `id`, `class`, `last_run`, `argument` FROM `*PREFIX*jobs` WHERE `id` > ? ORDER BY `id` ASC', 1);
		$query->execute(array($lastId));
		if ($row = $query->fetch()) {
			return $this->buildJob($row);
		} else {
			//begin at the start of the queue
			$query = $this->conn->prepare('SELECT `id`, `class`, `last_run`, `argument` FROM `*PREFIX*jobs` ORDER BY `id` ASC', 1);
			$query->execute();
			if ($row = $query->fetch()) {
				return $this->buildJob($row);
			} else {
				return null; //empty job list
			}
		}
	}

	/**
	 * @param int $id
	 * @return Job|null
	 */
	public function getById($id) {
		$query = $this->conn->prepare('SELECT `id`, `class`, `last_run`, `argument` FROM `*PREFIX*jobs` WHERE `id` = ?');
		$query->execute(array($id));
		if ($row = $query->fetch()) {
			return $this->buildJob($row);
		} else {
			return null;
		}
	}

	/**
	 * get the job object from a row in the db
	 *
	 * @param array $row
	 * @return Job
	 */
	private function buildJob($row) {
		$class = $row['class'];
		/**
		 * @var Job $job
		 */
		if (!class_exists($class)) {
			// job from disabled app or old version of an app, no need to do anything
			return null;
		}
		$job = new $class();
		$job->setId($row['id']);
		$job->setLastRun($row['last_run']);
		$job->setArgument(json_decode($row['argument'], true));
		return $job;
	}

	/**
	 * set the job that was last ran
	 *
	 * @param Job $job
	 */
	public function setLastJob($job) {
		$this->config->setAppValue('backgroundjob', 'lastjob', $job->getId());
	}

	/**
	 * get the id of the last ran job
	 *
	 * @return string
	 */
	public function getLastJob() {
		return $this->config->getAppValue('backgroundjob', 'lastjob', 0);
	}

	/**
	 * set the lastRun of $job to now
	 *
	 * @param Job $job
	 */
	public function setLastRun($job) {
		$query = $this->conn->prepare('UPDATE `*PREFIX*jobs` SET `last_run` = ? WHERE `id` = ?');
		$query->execute(array(time(), $job->getId()));
	}
}
