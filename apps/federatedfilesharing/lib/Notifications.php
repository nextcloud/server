<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OCA\FederatedFileSharing;

use OCP\AppFramework\Http;
use OCP\BackgroundJob\IJobList;
use OCP\Http\Client\IClientService;

class Notifications {
	const RESPONSE_FORMAT = 'json'; // default response format for ocs calls

	/** @var AddressHandler */
	private $addressHandler;

	/** @var IClientService */
	private $httpClientService;

	/** @var DiscoveryManager */
	private $discoveryManager;

	/** @var IJobList  */
	private $jobList;

	/**
	 * @param AddressHandler $addressHandler
	 * @param IClientService $httpClientService
	 * @param DiscoveryManager $discoveryManager
	 * @param IJobList $jobList
	 */
	public function __construct(
		AddressHandler $addressHandler,
		IClientService $httpClientService,
		DiscoveryManager $discoveryManager,
		IJobList $jobList
	) {
		$this->addressHandler = $addressHandler;
		$this->httpClientService = $httpClientService;
		$this->discoveryManager = $discoveryManager;
		$this->jobList = $jobList;
	}

	/**
	 * send server-to-server share to remote server
	 *
	 * @param string $token
	 * @param string $shareWith
	 * @param string $name
	 * @param int $remote_id
	 * @param string $owner
	 * @param string $ownerFederatedId
	 * @param string $sharedBy
	 * @param string $sharedByFederatedId
	 * @return bool
	 * @throws \OC\HintException
	 * @throws \OC\ServerNotAvailableException
	 */
	public function sendRemoteShare($token, $shareWith, $name, $remote_id, $owner, $ownerFederatedId, $sharedBy, $sharedByFederatedId) {

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
				'ownerFederatedId' => $ownerFederatedId,
				'sharedBy' => $sharedBy,
				'sharedByFederatedId' => $sharedByFederatedId,
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
	 * ask owner to re-share the file with the given user
	 *
	 * @param string $token
	 * @param int $id remote Id
	 * @param int $shareId internal share Id
	 * @param string $remote remote address of the owner
	 * @param string $shareWith
	 * @param int $permission
	 * @return bool
	 * @throws \OC\HintException
	 * @throws \OC\ServerNotAvailableException
	 */
	public function requestReShare($token, $id, $shareId, $remote, $shareWith, $permission) {

		$fields = array(
			'shareWith' => $shareWith,
			'token' => $token,
			'permission' => $permission,
			'remoteId' => $shareId
		);

		$url = $this->addressHandler->removeProtocolFromUrl($remote);
		$result = $this->tryHttpPostToShareEndpoint(rtrim($url, '/'), '/' . $id . '/reshare', $fields);
		$status = json_decode($result['result'], true);

		$httpRequestSuccessful = $result['success'];
		$ocsCallSuccessful = $status['ocs']['meta']['statuscode'] === 100 || $status['ocs']['meta']['statuscode'] === 200;
		$validToken = isset($status['ocs']['data']['token']) && is_string($status['ocs']['data']['token']);
		$validRemoteId = isset($status['ocs']['data']['remoteId']);

		if ($httpRequestSuccessful && $ocsCallSuccessful && $validToken && $validRemoteId) {
			return [
				$status['ocs']['data']['token'],
				(int)$status['ocs']['data']['remoteId']
			];
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
		$this->sendUpdateToRemote($remote, $id, $token, 'unshare');
	}

	/**
	 * send server-to-server unshare to remote server
	 *
	 * @param string $remote url
	 * @param int $id share id
	 * @param string $token
	 * @return bool
	 */
	public function sendRevokeShare($remote, $id, $token) {
		$this->sendUpdateToRemote($remote, $id, $token, 'revoke');
	}

	/**
	 * send notification to remote server if the permissions was changed
	 *
	 * @param string $remote
	 * @param int $remoteId
	 * @param string $token
	 * @param int $permissions
	 * @return bool
	 */
	public function sendPermissionChange($remote, $remoteId, $token, $permissions) {
		$this->sendUpdateToRemote($remote, $remoteId, $token, 'permissions', ['permissions' => $permissions]);
	}

	/**
	 * forward accept reShare to remote server
	 * 
	 * @param string $remote
	 * @param int $remoteId
	 * @param string $token
	 */
	public function sendAcceptShare($remote, $remoteId, $token) {
		$this->sendUpdateToRemote($remote, $remoteId, $token, 'accept');
	}

	/**
	 * forward decline reShare to remote server
	 *
	 * @param string $remote
	 * @param int $remoteId
	 * @param string $token
	 */
	public function sendDeclineShare($remote, $remoteId, $token) {
		$this->sendUpdateToRemote($remote, $remoteId, $token, 'decline');
	}

	/**
	 * inform remote server whether server-to-server share was accepted/declined
	 *
	 * @param string $remote
	 * @param string $token
	 * @param int $remoteId Share id on the remote host
	 * @param string $action possible actions: accept, decline, unshare, revoke, permissions
	 * @param array $data
	 * @param int $try
	 * @return boolean
	 */
	public function sendUpdateToRemote($remote, $remoteId, $token, $action, $data = [], $try = 0) {

		$fields = array('token' => $token);
		foreach ($data as $key => $value) {
			$fields[$key] = $value;
		}

		$url = $this->addressHandler->removeProtocolFromUrl($remote);
		$result = $this->tryHttpPostToShareEndpoint(rtrim($url, '/'), '/' . $remoteId . '/' . $action, $fields);
		$status = json_decode($result['result'], true);

		if ($result['success'] &&
			($status['ocs']['meta']['statuscode'] === 100 ||
				$status['ocs']['meta']['statuscode'] === 200
			)
		) {
			return true;
		} elseif ($try === 0) {
			// only add new job on first try
			$this->jobList->add('OCA\FederatedFileSharing\BackgroundJob\RetryJob',
				[
					'remote' => $remote,
					'remoteId' => $remoteId,
					'token' => $token,
					'action' => $action,
					'data' => json_encode($data),
					'try' => $try,
					'lastRun' => $this->getTimestamp()
				]
			);
		}

		return false;
	}


	/**
	 * return current timestamp
	 *
	 * @return int
	 */
	protected function getTimestamp() {
		return time();
	}

	/**
	 * try http post first with https and then with http as a fallback
	 *
	 * @param string $remoteDomain
	 * @param string $urlSuffix
	 * @param array $fields post parameters
	 * @return array
	 * @throws \Exception
	 */
	protected function tryHttpPostToShareEndpoint($remoteDomain, $urlSuffix, array $fields) {
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
					'body' => $fields,
					'timeout' => 10,
					'connect_timeout' => 10,
				]);
				$result['result'] = $response->getBody();
				$result['success'] = true;
				break;
			} catch (\Exception $e) {
				// if flat re-sharing is not supported by the remote server
				// we re-throw the exception and fall back to the old behaviour.
				// (flat re-shares has been introduced in Nextcloud 9.1)
				if ($e->getCode() === Http::STATUS_INTERNAL_SERVER_ERROR) {
					throw $e;
				}
				$try++;
				$protocol = 'http://';
			}
		}

		return $result;
	}
}
