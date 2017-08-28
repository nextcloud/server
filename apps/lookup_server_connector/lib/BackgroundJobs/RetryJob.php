<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\LookupServerConnector\BackgroundJobs;


use OC\BackgroundJob\Job;
use OC\BackgroundJob\JobList;
use OCP\BackgroundJob\IJobList;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\ILogger;

class RetryJob extends Job {
	/** @var IClientService */
	private $clientService;
	/** @var IJobList */
	private $jobList;
	/** @var string */
	private $lookupServer;
	/** @var int how much time should be between two tries (10 minutes) */
	private $interval = 600;

	/**
	 * @param IClientService $clientService
	 * @param IJobList $jobList
	 * @param IConfig $config
	 */
	public function __construct(IClientService $clientService,
								IJobList $jobList,
								IConfig $config) {
		$this->clientService = $clientService;
		$this->jobList = $jobList;

		$this->lookupServer = $config->getSystemValue('lookup_server', 'https://lookup.nextcloud.com');
		$this->lookupServer = rtrim($this->lookupServer, '/');
		$this->lookupServer .= '/users';
	}

	/**
	 * run the job, then remove it from the jobList
	 *
	 * @param JobList $jobList
	 * @param ILogger|null $logger
	 */
	public function execute($jobList, ILogger $logger = null) {
		if ($this->shouldRun($this->argument)) {
			parent::execute($jobList, $logger);
			$jobList->remove($this, $this->argument);
		}
	}

	protected function run($argument) {
		if($argument['retryNo'] === 5) {
			return;
		}

		$client = $this->clientService->newClient();

		try {
			$client->post($this->lookupServer,
				[
					'body' => json_encode($argument['dataArray']),
					'timeout' => 10,
					'connect_timeout' => 3,
				]
			);
		} catch (\Exception $e) {
			$this->jobList->add(RetryJob::class,
				[
					'dataArray' => $argument['dataArray'],
					'retryNo' => $argument['retryNo'] + 1,
					'lastRun' => time(),
				]
			);

		}
	}

	/**
	 * test if it is time for the next run
	 *
	 * @param array $argument
	 * @return bool
	 */
	protected function shouldRun($argument) {
		return !isset($argument['lastRun']) || ((time() - $argument['lastRun']) > $this->interval);
	}
}
