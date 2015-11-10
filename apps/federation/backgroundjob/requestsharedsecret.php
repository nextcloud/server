<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
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


namespace OCA\Federation\BackgroundJob;


use OC\BackgroundJob\QueuedJob;
use OCA\Federation\DbHandler;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http;
use OCP\BackgroundJob\IJobList;
use OCP\Http\Client\IClient;
use OCP\ILogger;
use OCP\IURLGenerator;

/**
 * Class RequestSharedSecret
 *
 * Ask remote ownCloud to request a sharedSecret from this server
 *
 * @package OCA\Federation\Backgroundjob
 */
class RequestSharedSecret extends QueuedJob {

	/** @var IClient */
	private $httpClient;

	/** @var IJobList */
	private $jobList;

	/** @var IURLGenerator */
	private $urlGenerator;

	private $endPoint = '/ocs/v2.php/apps/federation/api/v1/request-shared-secret?format=json';

	/**
	 * RequestSharedSecret constructor.
	 *
	 * @param IClient $httpClient
	 * @param IURLGenerator $urlGenerator
	 * @param IJobList $jobList
	 * @param TrustedServers $trustedServers
	 */
	public function __construct(
		IClient $httpClient = null,
		IURLGenerator $urlGenerator = null,
		IJobList $jobList = null,
		TrustedServers $trustedServers = null
	) {
		$this->httpClient = $httpClient ? $httpClient : \OC::$server->getHTTPClientService()->newClient();
		$this->jobList = $jobList ? $jobList : \OC::$server->getJobList();
		$this->urlGenerator = $urlGenerator ? $urlGenerator : \OC::$server->getURLGenerator();
		if ($trustedServers) {
			$this->trustedServers = $trustedServers;
		} else {
			$this->trustedServers = new TrustedServers(
				new DbHandler(\OC::$server->getDatabaseConnection(), \OC::$server->getL10N('federation')),
				\OC::$server->getHTTPClientService(),
				\OC::$server->getLogger(),
				$this->jobList,
				\OC::$server->getSecureRandom()
			);
		}
	}


	/**
	 * run the job, then remove it from the joblist
	 *
	 * @param JobList $jobList
	 * @param ILogger $logger
	 */
	public function execute($jobList, ILogger $logger = null) {
		$jobList->remove($this, $this->argument);
		$target = $this->argument['url'];
		// only execute if target is still in the list of trusted domains
		if ($this->trustedServers->isTrustedServer($target)) {
			parent::execute($jobList, $logger);
		}
	}

	protected function run($argument) {

		$target = $argument['url'];
		$source = $this->urlGenerator->getAbsoluteURL('/');
		$source = rtrim($source, '/');
		$token = $argument['token'];

		$result = $this->httpClient->post(
			$target . $this->endPoint,
			[
				'body' => [
					'url' => $source,
					'token' => $token,
				],
				'timeout' => 3,
				'connect_timeout' => 3,
			]
		);

		$status = $result->getStatusCode();

		// if we received a unexpected response we try again later
		if (
			$status !== Http::STATUS_OK
			&& $status !== Http::STATUS_FORBIDDEN
		) {
			$this->jobList->add(
				'OCA\Federation\BackgroundJob\RequestSharedSecret',
				$argument
			);
		}

	}
}
