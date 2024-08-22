<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Côme Chilliet <come@chilliet.eu>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OCA\Federation\BackgroundJob;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\Job;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IURLGenerator;
use OCP\OCS\IDiscoveryService;
use Psr\Log\LoggerInterface;

/**
 * Class RequestSharedSecret
 *
 * Ask remote Nextcloud to request a sharedSecret from this server
 *
 * @package OCA\Federation\Backgroundjob
 */
class RequestSharedSecret extends Job {
	private IClient $httpClient;

	protected bool $retainJob = false;

	private string $defaultEndPoint = '/ocs/v2.php/apps/federation/api/v1/request-shared-secret';

	/** @var int 30 day = 2592000sec */
	private int $maxLifespan = 2592000;

	public function __construct(
		IClientService $httpClientService,
		private IURLGenerator $urlGenerator,
		private IJobList $jobList,
		private TrustedServers $trustedServers,
		private IDiscoveryService $ocsDiscoveryService,
		private LoggerInterface $logger,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
		$this->httpClient = $httpClientService->newClient();
	}


	/**
	 * run the job, then remove it from the joblist
	 */
	public function start(IJobList $jobList): void {
		$target = $this->argument['url'];
		// only execute if target is still in the list of trusted domains
		if ($this->trustedServers->isTrustedServer($target)) {
			$this->parentStart($jobList);
		}

		$jobList->remove($this, $this->argument);

		if ($this->retainJob) {
			$this->reAddJob($this->argument);
		}
	}

	/**
	 * Call start() method of parent
	 * Useful for unit tests
	 */
	protected function parentStart(IJobList $jobList): void {
		parent::start($jobList);
	}

	/**
	 * @param array $argument
	 * @return void
	 */
	protected function run($argument) {
		$target = $argument['url'];
		$created = isset($argument['created']) ? (int)$argument['created'] : $this->time->getTime();
		$currentTime = $this->time->getTime();
		$source = $this->urlGenerator->getAbsoluteURL('/');
		$source = rtrim($source, '/');
		$token = $argument['token'];

		// kill job after 30 days of trying
		$deadline = $currentTime - $this->maxLifespan;
		if ($created < $deadline) {
			$this->logger->warning("The job to request the shared secret job is too old and gets stopped now without retention. Setting server status of '{$target}' to failure.");
			$this->retainJob = false;
			$this->trustedServers->setServerStatus($target, TrustedServers::STATUS_FAILURE);
			return;
		}

		$endPoints = $this->ocsDiscoveryService->discover($target, 'FEDERATED_SHARING');
		$endPoint = $endPoints['shared-secret'] ?? $this->defaultEndPoint;

		// make sure that we have a well formatted url
		$url = rtrim($target, '/') . '/' . trim($endPoint, '/');

		try {
			$result = $this->httpClient->post(
				$url,
				[
					'body' => [
						'url' => $source,
						'token' => $token,
						'format' => 'json',
					],
					'timeout' => 3,
					'connect_timeout' => 3,
				]
			);

			$status = $result->getStatusCode();
		} catch (ClientException $e) {
			$status = $e->getCode();
			if ($status === Http::STATUS_FORBIDDEN) {
				$this->logger->info($target . ' refused to ask for a shared secret.', ['app' => 'federation']);
			} else {
				$this->logger->info($target . ' responded with a ' . $status . ' containing: ' . $e->getMessage(), ['app' => 'federation']);
			}
		} catch (RequestException $e) {
			$status = -1; // There is no status code if we could not connect
			$this->logger->info('Could not connect to ' . $target, ['app' => 'federation']);
		} catch (\Throwable $e) {
			$status = Http::STATUS_INTERNAL_SERVER_ERROR;
			$this->logger->error($e->getMessage(), ['app' => 'federation', 'exception' => $e]);
		}

		// if we received a unexpected response we try again later
		if (
			$status !== Http::STATUS_OK
			&& ($status !== Http::STATUS_FORBIDDEN || $this->getAttempt($argument) < 5)
		) {
			$this->retainJob = true;
		}
	}

	/**
	 * re-add background job
	 */
	protected function reAddJob(array $argument): void {
		$url = $argument['url'];
		$created = isset($argument['created']) ? (int)$argument['created'] : $this->time->getTime();
		$token = $argument['token'];
		$attempt = $this->getAttempt($argument) + 1;

		$this->jobList->add(
			RequestSharedSecret::class,
			[
				'url' => $url,
				'token' => $token,
				'created' => $created,
				'attempt' => $attempt
			]
		);
	}

	protected function getAttempt(array $argument): int {
		return $argument['attempt'] ?? 0;
	}
}
