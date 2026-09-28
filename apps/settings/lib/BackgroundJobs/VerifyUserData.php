<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\BackgroundJobs;

use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\Job;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class VerifyUserData extends Job {
	/** @var bool */
	private bool $retainJob = true;

	/** @var int max number of attempts to send the request */
	private int $maxTry = 24;

	/** @var int how much time should be between two tries (1 hour) */
	private int $interval = 3600;
	private string $lookupServerUrl;

	public function __construct(
		private IAccountManager $accountManager,
		private IUserManager $userManager,
		private IClientService $httpClientService,
		private LoggerInterface $logger,
		ITimeFactory $timeFactory,
		private IConfig $config,
	) {
		parent::__construct($timeFactory);

		$lookupServerUrl = $config->getSystemValue('lookup_server', 'https://lookup.nextcloud.com');
		$this->lookupServerUrl = rtrim($lookupServerUrl, '/');
	}

	public function start(IJobList $jobList): void {
		if ($this->shouldRun($this->argument)) {
			parent::start($jobList);
			$jobList->remove($this, $this->argument);
			if ($this->retainJob) {
				$this->reAddJob($jobList, $this->argument);
			} else {
				$this->resetVerificationState();
			}
		}
	}

	protected function run($argument) {
		$try = (int)$argument['try'] + 1;

		switch ($argument['type']) {
			case IAccountManager::PROPERTY_WEBSITE:
				$result = $this->verifyWebsite($argument);
				break;
			case IAccountManager::PROPERTY_TWITTER:
			case IAccountManager::PROPERTY_EMAIL:
				$result = $this->verifyViaLookupServer($argument, $argument['type']);
				break;
			default:
				// no valid type given, no need to retry
				$this->logger->error($argument['type'] . ' is no valid type for user account data.');
				$result = true;
		}

		if ($result === true || $try > $this->maxTry) {
			$this->retainJob = false;
		}
	}

	/**
	 * verify web page
	 *
	 * @param array $argument
	 * @return bool true if we could check the verification code, otherwise false
	 */
	protected function verifyWebsite(array $argument) {
		$result = false;

		$url = rtrim($argument['data'], '/') . '/.well-known/' . 'CloudIdVerificationCode.txt';

		$client = $this->httpClientService->newClient();
		try {
			$response = $client->get($url);
		} catch (\Exception $e) {
			return false;
		}

		if ($response->getStatusCode() === Http::STATUS_OK) {
			$result = true;
			$publishedCode = $response->getBody();
			// remove new lines and spaces
			$publishedCodeSanitized = trim(preg_replace('/\s\s+/', ' ', $publishedCode));
			$user = $this->userManager->get($argument['uid']);
			// we don't check a valid user -> give up
			if ($user === null) {
				$this->logger->error($argument['uid'] . ' doesn\'t exist, can\'t verify user data.');
				return $result;
			}
			$userAccount = $this->accountManager->getAccount($user);
			$websiteProp = $userAccount->getProperty(IAccountManager::PROPERTY_WEBSITE);
			$websiteProp->setVerified($publishedCodeSanitized === $argument['verificationCode']
				? IAccountManager::VERIFIED
				: IAccountManager::NOT_VERIFIED
			);
			$this->accountManager->updateAccount($userAccount);
		}

		return $result;
	}

	protected function verifyViaLookupServer(array $argument, string $dataType): bool {
		// TODO: Consider to enable for non-global-scale setups by checking 'files_sharing', 'lookupServerUploadEnabled'
		if (!$this->config->getSystemValueBool('gs.enabled', false)
			|| empty($this->lookupServerUrl)
			|| $this->config->getSystemValue('has_internet_connection', true) === false
		) {
			return true;
		}

		$user = $this->userManager->get($argument['uid']);

		// we don't check a valid user -> give up
		if ($user === null) {
			$this->logger->info($argument['uid'] . ' doesn\'t exist, can\'t verify user data.');
			return true;
		}

		$cloudId = $user->getCloudId();
		$lookupServerData = $this->queryLookupServer($cloudId);

		// for some reasons we couldn't read any data from the lookup server, try again later
		if (empty($lookupServerData) || empty($lookupServerData[$dataType])) {
			return false;
		}

		// lookup server has verification data for wrong user data (e.g. email address), try again later
		if ($lookupServerData[$dataType]['value'] !== $argument['data']) {
			return false;
		}

		// lookup server hasn't verified the email address so far, try again later
		if ($lookupServerData[$dataType]['verified'] === IAccountManager::NOT_VERIFIED) {
			return false;
		}

		try {
			$userAccount = $this->accountManager->getAccount($user);
			$property = $userAccount->getProperty($dataType);
			$property->setVerified(IAccountManager::VERIFIED);
			$this->accountManager->updateAccount($userAccount);
		} catch (PropertyDoesNotExistException $e) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $cloudId
	 * @return array
	 */
	protected function queryLookupServer($cloudId) {
		try {
			$client = $this->httpClientService->newClient();
			$response = $client->get(
				$this->lookupServerUrl . '/users?search=' . urlencode($cloudId) . '&exactCloudId=1',
				[
					'timeout' => 10,
					'connect_timeout' => 3,
				]
			);

			$body = json_decode($response->getBody(), true);

			if (is_array($body) && isset($body['federationId']) && $body['federationId'] === $cloudId) {
				return $body;
			}
		} catch (\Exception $e) {
			// do nothing, we will just re-try later
		}

		return [];
	}

	/**
	 * re-add background job with new arguments
	 *
	 * @param IJobList $jobList
	 * @param array $argument
	 */
	protected function reAddJob(IJobList $jobList, array $argument) {
		$jobList->add(VerifyUserData::class,
			[
				'verificationCode' => $argument['verificationCode'],
				'data' => $argument['data'],
				'type' => $argument['type'],
				'uid' => $argument['uid'],
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


	/**
	 * reset verification state after max tries are reached
	 */
	protected function resetVerificationState(): void {
		$user = $this->userManager->get($this->argument['uid']);
		if ($user !== null) {
			$userAccount = $this->accountManager->getAccount($user);
			try {
				$property = $userAccount->getProperty($this->argument['type']);
				$property->setVerified(IAccountManager::NOT_VERIFIED);
				$this->accountManager->updateAccount($userAccount);
			} catch (PropertyDoesNotExistException $e) {
				return;
			}
		}
	}
}
