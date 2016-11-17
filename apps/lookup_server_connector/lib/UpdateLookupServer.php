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


namespace OCA\LookupServerConnector;


use OC\Accounts\AccountManager;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IUser;
use OCP\Security\ISecureRandom;

/**
 * Class UpdateLookupServer
 *
 * @package OCA\LookupServerConnector
 */
class UpdateLookupServer {

	/** @var  AccountManager */
	private $accountManager;

	/** @var IConfig */
	private $config;

	/** @var ISecureRandom */
	private $secureRandom;

	/** @var IClientService */
	private $clientService;

	/** @var string URL point to lookup server */
	private $lookupServer = 'http://192.168.56.102';

	/**
	 * UpdateLookupServer constructor.
	 *
	 * @param AccountManager $accountManager
	 * @param IConfig $config
	 * @param ISecureRandom $secureRandom
	 * @param IClientService $clientService
	 */
	public function __construct(AccountManager $accountManager,
								IConfig $config,
								ISecureRandom $secureRandom,
								IClientService $clientService) {
		$this->accountManager = $accountManager;
		$this->config = $config;
		$this->secureRandom = $secureRandom;
		$this->clientService = $clientService;
	}


	public function userUpdated(IUser $user) {
		$userData = $this->accountManager->getUser($user);
		$authKey = $this->config->getUserValue($user->getUID(), 'lookup_server_connector', 'authKey');

		$publicData = [];

		foreach ($userData as $key => $data) {
			if ($data['scope'] === AccountManager::VISIBILITY_PUBLIC) {
				$publicData[$key] = $data;
			}
		}

		if (empty($publicData) && !empty($authKey)) {
			$this->removeFromLookupServer($user, $authKey);
		} else {
			$this->sendToLookupServer($user, $publicData, $authKey);
		}
	}

	/**
	 * remove user from lookup server
	 *
	 * @param IUser $user
	 */
	protected function removeFromLookupServer(IUser $user) {
		$this->config->deleteUserValue($user->getUID(), 'lookup_server_connector', 'authKey');
	}

	/**
	 * send public user data to the lookup server
	 *
	 * @param IUser $user
	 * @param array $publicData
	 * @param string $authKey
	 */
	protected function sendToLookupServer(IUser $user, $publicData, $authKey) {
		if (empty($authKey)) {
			$authKey = $this->secureRandom->generate(16);
			$this->sendNewRecord($user, $publicData, $authKey);
			$this->config->setUserValue($user->getUID(), 'lookup_server_connector', 'authKey', $authKey);
		} else {
			$this->updateExistingRecord($user, $publicData, $authKey);
		}
	}

	protected function sendNewRecord(IUser $user, $publicData, $authKey) {
		$httpClient = $this->clientService->newClient();
		$response = $httpClient->post($this->lookupServer,
			[
				'body' => [
					'key' => $authKey,
					'federationid' => $publicData[$user->getCloudId()],
					'name' => isset($publicData[AccountManager::PROPERTY_DISPLAYNAME]) ? $publicData[AccountManager::PROPERTY_DISPLAYNAME]['value'] : '',
					'email' => isset($publicData[AccountManager::PROPERTY_EMAIL]) ? $publicData[AccountManager::PROPERTY_EMAIL]['value'] : '',
					'address' => isset($publicData[AccountManager::PROPERTY_ADDRESS]) ? $publicData[AccountManager::PROPERTY_ADDRESS]['value'] : '',
					'website' => isset($publicData[AccountManager::PROPERTY_WEBSITE]) ? $publicData[AccountManager::PROPERTY_WEBSITE]['value'] : '',
					'twitter' => isset($publicData[AccountManager::PROPERTY_TWITTER]) ? $publicData[AccountManager::PROPERTY_TWITTER]['value'] : '',
					'phone' => isset($publicData[AccountManager::PROPERTY_PHONE]) ? $publicData[AccountManager::PROPERTY_PHONE]['value'] : '',
				],
				'timeout' => 3,
				'connect_timeout' => 3,
			]
		);
	}

	protected function updateExistingRecord(IUser $user, $publicData, $authKey) {
		$httpClient = $this->clientService->newClient();
		$httpClient->put($this->lookupServer,
			[
				'body' => [
					'key' => $authKey,
					'federationid' => $publicData[$user->getCloudId()],
					'name' => isset($publicData[AccountManager::PROPERTY_DISPLAYNAME]) ? $publicData[AccountManager::PROPERTY_DISPLAYNAME]['value'] : '',
					'email' => isset($publicData[AccountManager::PROPERTY_EMAIL]) ? $publicData[AccountManager::PROPERTY_EMAIL]['value'] : '',
					'address' => isset($publicData[AccountManager::PROPERTY_ADDRESS]) ? $publicData[AccountManager::PROPERTY_ADDRESS]['value'] : '',
					'website' => isset($publicData[AccountManager::PROPERTY_WEBSITE]) ? $publicData[AccountManager::PROPERTY_WEBSITE]['value'] : '',
					'twitter' => isset($publicData[AccountManager::PROPERTY_TWITTER]) ? $publicData[AccountManager::PROPERTY_TWITTER]['value'] : '',
					'phone' => isset($publicData[AccountManager::PROPERTY_PHONE]) ? $publicData[AccountManager::PROPERTY_PHONE]['value'] : '',
				],
				'timeout' => 3,
				'connect_timeout' => 3,
			]
		);

	}
}
