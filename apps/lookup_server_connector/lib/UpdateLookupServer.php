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
use OCP\IConfig;
use OCP\IUser;

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

	/**
	 * UpdateLookupServer constructor.
	 *
	 * @param AccountManager $accountManager
	 * @param IConfig $config
	 */
	public function __construct(AccountManager $accountManager, IConfig $config) {
		$this->accountManager;
		$this->config = $config;
	}


	public function userUpdated(IUser $user) {
		$userData = $this->accountManager->getUser($user);
		$authKey = $this->config->getUserValue($user->getUID(), 'lookup_server_connector', 'authKey');

		$publicData = [];

		foreach ($userData as $data) {
			if ($data['scope'] === AccountManager::VISIBILITY_PUBLIC) {
				$publicData[] = $data;
			}
		}

		if (empty($publicData)) {
			$this->removeFromLookupServer($user);
		} else {
			$this->sendToLookupServer($publicData, $authKey);
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
	 * @param array $publicData
	 * @param string $authKey
	 */
	protected function sendToLookupServer($publicData, $authKey) {
		// If we don't update a existing entry, the server will return a authKey and we
		// will add it to the database
	}
}
