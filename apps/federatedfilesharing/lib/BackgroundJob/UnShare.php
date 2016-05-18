<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
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


namespace OCA\FederatedFileSharing\BackgroundJob;


use OC\BackgroundJob\Job;
use OC\BackgroundJob\JobList;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\DiscoveryManager;
use OCA\FederatedFileSharing\Notifications;
use OCP\BackgroundJob\IJobList;
use OCP\ILogger;

/**
 * Class UnShare
 *
 * Background job to re-send the un-share notification to the remote server in
 * case the server was not available on the first try
 *
 * @package OCA\FederatedFileSharing\BackgroundJob
 */
class UnShare extends Job {

	/** @var  bool */
	private $retainJob = true;
	
	/** @var Notifications */
	private $notifications;

	/** @var int max number of attempts to send the un-share request */
	private $maxTry = 10;

	/** @var int how much time should be between two tries (12 hours) */
	private $interval = 43200;

	/**
	 * UnShare constructor.
	 *
	 * @param Notifications $notifications
	 */
	public function __construct(Notifications $notifications = null) {
		if ($notifications) {
			$this->notifications = $notifications;
		} else {
			$addressHandler = new AddressHandler(
				\OC::$server->getURLGenerator(),
				\OC::$server->getL10N('federatedfilesharing')
			);
			$discoveryManager = new DiscoveryManager(
				\OC::$server->getMemCacheFactory(),
				\OC::$server->getHTTPClientService()
			);
			$this->notifications = new Notifications(
				$addressHandler,
				\OC::$server->getHTTPClientService(),
				$discoveryManager,
				\OC::$server->getJobList()
			);
		}
		
	}

	/**
	 * run the job, then remove it from the jobList
	 *
	 * @param JobList $jobList
	 * @param ILogger $logger
	 */
	public function execute($jobList, ILogger $logger = null) {

		if ($this->shouldRun($this->argument)) {
			parent::execute($jobList, $logger);
			$jobList->remove($this, $this->argument);
			if ($this->retainJob) {
				$this->reAddJob($jobList, $this->argument);
			}
		}
	}

	protected function run($argument) {
		$remote = $argument['remote'];
		$id = (int)$argument['id'];
		$token = $argument['token'];
		$try = (int)$argument['try'] + 1;

		$result = $this->notifications->sendRemoteUnShare($remote, $id, $token, $try);

		if ($result === true || $try > $this->maxTry) {
			$this->retainJob = false;
		}
	}

	/**
	 * re-add background job with new arguments
	 *
	 * @param IJobList $jobList
	 * @param array $argument
	 */
	protected function reAddJob(IJobList $jobList, array $argument) {
		$jobList->add('OCA\FederatedFileSharing\BackgroundJob\UnShare',
			[
				'remote' => $argument['remote'],
				'id' => $argument['id'],
				'token' => $argument['token'],
				'try' => (int)$argument['try'] + 1,
				'lastRun' => time()
			]
		);
	}

	/**
	 * test if it is time for the next run
	 *
	 * @param array $argument
	 * @return bool
	 */
	protected function shouldRun(array $argument) {
		$lastRun = (int)$argument['lastRun'];
		return ((time() - $lastRun) > $this->interval);
	}

}
