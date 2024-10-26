<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\LookupServerConnector\BackgroundJobs;

use OC\Security\IdentityProof\Signer;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\Job;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;

class RetryJob extends Job {
	private string $lookupServer;
	private Signer $signer;
	protected int $retries = 0;
	protected bool $retainJob = false;

	/**
	 * @param ITimeFactory $time
	 * @param IClientService $clientService
	 * @param IConfig $config
	 * @param IUserManager $userManager
	 * @param IAccountManager $accountManager
	 * @param Signer $signer
	 */
	public function __construct(
		ITimeFactory $time,
		private IClientService $clientService,
		private IConfig $config,
		private IUserManager $userManager,
		private IAccountManager $accountManager,
		Signer $signer,
	) {
		parent::__construct($time);
		$this->signer = $signer;

		$this->lookupServer = $this->config->getSystemValue('lookup_server', 'https://lookup.nextcloud.com');
		if (!empty($this->lookupServer)) {
			$this->lookupServer = rtrim($this->lookupServer, '/');
			$this->lookupServer .= '/users';
		}
	}

	/**
	 * Run the job, then remove it from the jobList
	 */
	public function start(IJobList $jobList): void {
		if (!isset($this->argument['userId'])) {
			// Old background job without user id, just drop it.
			$jobList->remove($this, $this->argument);
			return;
		}

		$this->retries = (int)$this->config->getUserValue($this->argument['userId'], 'lookup_server_connector', 'update_retries', '0');

		if ($this->shouldRemoveBackgroundJob()) {
			$jobList->remove($this, $this->argument);
			return;
		}

		if ($this->shouldRun()) {
			parent::start($jobList);
			if (!$this->retainJob) {
				$jobList->remove($this, $this->argument);
			}
		}
	}

	/**
	 * Check if we should kill the background job:
	 *
	 * - internet connection is disabled
	 * - no valid lookup server URL given
	 * - lookup server was disabled by the admin
	 * - max retries are reached (set to 5)
	 */
	protected function shouldRemoveBackgroundJob(): bool {
		return $this->config->getSystemValueBool('has_internet_connection', true) === false ||
			$this->config->getSystemValueString('lookup_server', 'https://lookup.nextcloud.com') === '' ||
			$this->config->getAppValue('files_sharing', 'lookupServerUploadEnabled', 'yes') !== 'yes' ||
			$this->retries >= 5;
	}

	protected function shouldRun(): bool {
		$delay = 100 * 6 ** $this->retries;
		return ($this->time->getTime() - $this->lastRun) > $delay;
	}

	protected function run($argument): void {
		$user = $this->userManager->get($this->argument['userId']);
		if (!$user instanceof IUser) {
			// User does not exist anymore
			return;
		}

		$data = $this->getUserAccountData($user);
		$signedData = $this->signer->sign('lookupserver', $data, $user);
		$client = $this->clientService->newClient();

		try {
			if (count($data) === 1) {
				$dataOnLookupServer = $this->config->getUserValue($user->getUID(), 'lookup_server_connector', 'dataSend', '0') === '1';

				if (!$dataOnLookupServer) {
					// We never send data to the lookupserver so no need to delete it
					return;
				}

				// There is data on the lookup server so we must delete it
				$client->delete($this->lookupServer,
					[
						'body' => json_encode($signedData),
						'timeout' => 10,
						'connect_timeout' => 3,
					]
				);

				$this->config->setUserValue($user->getUID(), 'lookup_server_connector', 'dataSend', '0');
			} else {
				$client->post($this->lookupServer,
					[
						'body' => json_encode($signedData),
						'timeout' => 10,
						'connect_timeout' => 3,
					]
				);
				$this->config->setUserValue($user->getUID(), 'lookup_server_connector', 'dataSend', '1');
			}

			// Reset retry counter
			$this->config->deleteUserValue(
				$user->getUID(),
				'lookup_server_connector',
				'update_retries'
			);
		} catch (\Exception $e) {
			// An error occurred, retry later
			$this->retainJob = true;
			$this->config->setUserValue(
				$user->getUID(),
				'lookup_server_connector',
				'update_retries',
				$this->retries + 1
			);
		}
	}

	protected function getUserAccountData(IUser $user): array {
		$account = $this->accountManager->getAccount($user);

		$publicData = [];
		foreach ($account->getProperties() as $property) {
			if ($property->getScope() === IAccountManager::SCOPE_PUBLISHED) {
				$publicData[$property->getName()] = $property->getValue();
			}
		}

		$data = ['federationId' => $user->getCloudId()];
		if (!empty($publicData)) {
			$data['name'] = $publicData[IAccountManager::PROPERTY_DISPLAYNAME]['value'] ?? '';
			$data['email'] = $publicData[IAccountManager::PROPERTY_EMAIL]['value'] ?? '';
			$data['address'] = $publicData[IAccountManager::PROPERTY_ADDRESS]['value'] ?? '';
			$data['website'] = $publicData[IAccountManager::PROPERTY_WEBSITE]['value'] ?? '';
			$data['twitter'] = $publicData[IAccountManager::PROPERTY_TWITTER]['value'] ?? '';
			$data['phone'] = $publicData[IAccountManager::PROPERTY_PHONE]['value'] ?? '';
			$data['twitter_signature'] = $publicData[IAccountManager::PROPERTY_TWITTER]['signature'] ?? '';
			$data['website_signature'] = $publicData[IAccountManager::PROPERTY_WEBSITE]['signature'] ?? '';
			$data['verificationStatus'] = [
				IAccountManager::PROPERTY_WEBSITE => $publicData[IAccountManager::PROPERTY_WEBSITE]['verified'] ?? '',
				IAccountManager::PROPERTY_TWITTER => $publicData[IAccountManager::PROPERTY_TWITTER]['verified'] ?? '',
			];
		}

		return $data;
	}
}
