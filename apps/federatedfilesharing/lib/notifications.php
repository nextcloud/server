<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
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


namespace OCA\FederatedFileSharing;

use OCP\Http\Client\IClientService;

class Notifications {
	const RESPONSE_FORMAT = 'json'; // default response format for ocs calls

	/** @var AddressHandler */
	private $addressHandler;
	/** @var IClientService */
	private $httpClientService;
	/** @var DiscoveryManager */
	private $discoveryManager;

	/**
	 * @param AddressHandler $addressHandler
	 * @param IClientService $httpClientService
	 * @param DiscoveryManager $discoveryManager
	 */
	public function __construct(
		AddressHandler $addressHandler,
		IClientService $httpClientService,
		DiscoveryManager $discoveryManager
	) {
		$this->addressHandler = $addressHandler;
		$this->httpClientService = $httpClientService;
		$this->discoveryManager = $discoveryManager;
	}

	/**
	 * send server-to-server share to remote server
	 *
	 * @param string $token
	 * @param string $shareWith
	 * @param string $name
	 * @param int $remote_id
	 * @param string $owner
	 * @return bool
	 */
	public function sendRemoteShare($token, $shareWith, $name, $remote_id, $owner) {

		list($user, $remote) = $this->addressHandler->splitUserRemote($shareWith);

		if ($user && $remote) {
			$url = $remote;
			$local = $this->addressHandler->generateRemoteURL();

			$fields = array(
				'shareWith' => $user,
				'token' => $token,
				'name' => $name,
				'remoteId' => $remote_id,
				'owner' => $owner,
				'remote' => $local,
			);

			$url = $this->addressHandler->removeProtocolFromUrl($url);
			$result = $this->tryHttpPostToShareEndpoint($url, '', $fields);
			$status = json_decode($result['result'], true);

			if ($result['success'] && ($status['ocs']['meta']['statuscode'] === 100 || $status['ocs']['meta']['statuscode'] === 200)) {
				\OC_Hook::emit('OCP\Share', 'federated_share_added', ['server' => $remote]);
				return true;
			}

		}

		return false;
	}

	/**
	 * send server-to-server unshare to remote server
	 *
	 * @param string $remote url
	 * @param int $id share id
	 * @param string $token
	 * @return bool
	 */
	public function sendRemoteUnShare($remote, $id, $token) {
		$url = rtrim($remote, '/');
		$fields = array('token' => $token, 'format' => 'json');
		$url = $this->addressHandler->removeProtocolFromUrl($url);
		$result = $this->tryHttpPostToShareEndpoint($url, '/'.$id.'/unshare', $fields);
		$status = json_decode($result['result'], true);

		return ($result['success'] && ($status['ocs']['meta']['statuscode'] === 100 || $status['ocs']['meta']['statuscode'] === 200));
	}

	/**
	 * try http post first with https and then with http as a fallback
	 *
	 * @param string $remoteDomain
	 * @param string $urlSuffix
	 * @param array $fields post parameters
	 * @return array
	 */
	private function tryHttpPostToShareEndpoint($remoteDomain, $urlSuffix, array $fields) {
		$client = $this->httpClientService->newClient();
		$protocol = 'https://';
		$result = [
			'success' => false,
			'result' => '',
		];
		$try = 0;

		while ($result['success'] === false && $try < 2) {
			$endpoint = $this->discoveryManager->getShareEndpoint($protocol . $remoteDomain);
			try {
				$response = $client->post($protocol . $remoteDomain . $endpoint . $urlSuffix . '?format=' . self::RESPONSE_FORMAT, [
					'body' => $fields
				]);
				$result['result'] = $response->getBody();
				$result['success'] = true;
				break;
			} catch (\Exception $e) {
				$try++;
				$protocol = 'http://';
			}
		}

		return $result;
	}
}
