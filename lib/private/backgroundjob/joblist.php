<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
		if ($class === 'OC_Cache_FileGlobalGC') {
			$class = '\OC\Cache\FileGlobalGC';
		}
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
