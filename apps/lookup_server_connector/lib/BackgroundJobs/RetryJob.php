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
use OCP\BackgroundJob\IJobList;
use OCP\Http\Client\IClientService;

class RetryJob extends Job {
	/** @var IClientService */
	private $clientService;
	/** @var IJobList */
	private $jobList;
	/** @var string */
	private $lookupServer = 'https://lookup.nextcloud.com/users';

	/**
	 * @param IClientService|null $clientService
	 * @param IJobList|null $jobList
	 */
	public function __construct(IClientService $clientService = null,
								IJobList $jobList = null) {
		if($clientService !== null) {
			$this->clientService = $clientService;
		} else {
			$this->clientService = \OC::$server->getHTTPClientService();
		}
		if($jobList !== null) {
			$this->jobList = $jobList;
		} else {
			$this->jobList = \OC::$server->getJobList();
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
				]
			);

		}
	}
}
