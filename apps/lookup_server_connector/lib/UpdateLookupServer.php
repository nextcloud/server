<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\LookupServerConnector;

use OC\Accounts\AccountManager;
use OC\Security\IdentityProof\Signer;
use OCA\LookupServerConnector\BackgroundJobs\RetryJob;
use OCP\BackgroundJob\IJobList;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IUser;

/**
 * Class UpdateLookupServer
 *
 * @package OCA\LookupServerConnector
 */
class UpdateLookupServer {
	/** @var AccountManager */
	private $accountManager;
	/** @var IClientService */
	private $clientService;
	/** @var Signer */
	private $signer;
	/** @var IJobList */
	private $jobList;
	/** @var string URL point to lookup server */
	private $lookupServer;

	/**
	 * @param AccountManager $accountManager
	 * @param IClientService $clientService
	 * @param Signer $signer
	 * @param IJobList $jobList
	 * @param IConfig $config
	 */
	public function __construct(AccountManager $accountManager,
								IClientService $clientService,
								Signer $signer,
								IJobList $jobList,
								IConfig $config) {
		$this->accountManager = $accountManager;
		$this->clientService = $clientService;
		$this->signer = $signer;
		$this->jobList = $jobList;

		$this->lookupServer = $config->getSystemValue('lookup_server', 'https://lookup.nextcloud.com');
		$this->lookupServer = rtrim($this->lookupServer, '/');
		$this->lookupServer .= '/users';
	}

	/**
	 * @param IUser $user
	 */
	public function userUpdated(IUser $user) {
		$userData = $this->accountManager->getUser($user);
		$publicData = [];

		foreach ($userData as $key => $data) {
			if ($data['scope'] === AccountManager::VISIBILITY_PUBLIC) {
				$publicData[$key] = $data;
			}
		}

		$this->sendToLookupServer($user, $publicData);
	}

	/**
	 * send public user data to the lookup server
	 *
	 * @param IUser $user
	 * @param array $publicData
	 */
	protected function sendToLookupServer(IUser $user, array $publicData) {

		$dataArray = ['federationId' => $user->getCloudId()];

		if (!empty($publicData)) {
			$dataArray['name'] = isset($publicData[AccountManager::PROPERTY_DISPLAYNAME]) ? $publicData[AccountManager::PROPERTY_DISPLAYNAME]['value'] : '';
			$dataArray['email'] = isset($publicData[AccountManager::PROPERTY_EMAIL]) ? $publicData[AccountManager::PROPERTY_EMAIL]['value'] : '';
			$dataArray['address'] = isset($publicData[AccountManager::PROPERTY_ADDRESS]) ? $publicData[AccountManager::PROPERTY_ADDRESS]['value'] : '';
			$dataArray['website'] = isset($publicData[AccountManager::PROPERTY_WEBSITE]) ? $publicData[AccountManager::PROPERTY_WEBSITE]['value'] : '';
			$dataArray['twitter'] = isset($publicData[AccountManager::PROPERTY_TWITTER]) ? $publicData[AccountManager::PROPERTY_TWITTER]['value'] : '';
			$dataArray['phone'] = isset($publicData[AccountManager::PROPERTY_PHONE]) ? $publicData[AccountManager::PROPERTY_PHONE]['value'] : '';
			$dataArray['twitter_signature'] = isset($publicData[AccountManager::PROPERTY_TWITTER]['signature']) ? $publicData[AccountManager::PROPERTY_TWITTER]['signature'] : '';
			$dataArray['website_signature'] = isset($publicData[AccountManager::PROPERTY_WEBSITE]['signature']) ? $publicData[AccountManager::PROPERTY_WEBSITE]['signature'] : '';
			$dataArray['verificationStatus'] =
				[
					AccountManager::PROPERTY_WEBSITE => isset($publicData[AccountManager::PROPERTY_WEBSITE]) ? $publicData[AccountManager::PROPERTY_WEBSITE]['verified'] : '',
					AccountManager::PROPERTY_TWITTER => isset($publicData[AccountManager::PROPERTY_TWITTER]) ? $publicData[AccountManager::PROPERTY_TWITTER]['verified'] : '',
				];
		}

		$dataArray = $this->signer->sign('lookupserver', $dataArray, $user);
		$httpClient = $this->clientService->newClient();
		try {
			if (empty($publicData)) {
				$httpClient->delete($this->lookupServer,
					[
						'body' => json_encode($dataArray),
						'timeout' => 10,
						'connect_timeout' => 3,
					]
				);
			} else {
				$httpClient->post($this->lookupServer,
					[
						'body' => json_encode($dataArray),
						'timeout' => 10,
						'connect_timeout' => 3,
					]
				);
			}
		} catch (\Exception $e) {
			$this->jobList->add(RetryJob::class,
				[
					'dataArray' => $dataArray,
					'retryNo' => 0,
				]
			);
		}
	}
}
