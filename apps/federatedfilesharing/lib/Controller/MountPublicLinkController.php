<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2016, Björn Schießle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
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


namespace OCA\FederatedFileSharing\Controller;

use OC\HintException;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\Client\IClientService;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Share\IManager;

/**
 * Class MountPublicLinkController
 *
 * convert public links to federated shares
 *
 * @package OCA\FederatedFileSharing\Controller
 */
class MountPublicLinkController extends Controller {

	/** @var FederatedShareProvider */
	private $federatedShareProvider;

	/** @var AddressHandler */
	private $addressHandler;

	/** @var IManager  */
	private $shareManager;

	/** @var  ISession */
	private $session;

	/** @var IL10N */
	private $l;

	/** @var IUserSession */
	private $userSession;

	/** @var IClientService */
	private $clientService;

	/**
	 * MountPublicLinkController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param FederatedShareProvider $federatedShareProvider
	 * @param IManager $shareManager
	 * @param AddressHandler $addressHandler
	 * @param ISession $session
	 * @param IL10N $l
	 * @param IUserSession $userSession
	 * @param IClientService $clientService
	 */
	public function __construct($appName,
								IRequest $request,
								FederatedShareProvider $federatedShareProvider,
								IManager $shareManager,
								AddressHandler $addressHandler,
								ISession $session,
								IL10N $l,
								IUserSession $userSession,
								IClientService $clientService
	) {
		parent::__construct($appName, $request);

		$this->federatedShareProvider = $federatedShareProvider;
		$this->shareManager = $shareManager;
		$this->addressHandler = $addressHandler;
		$this->session = $session;
		$this->l = $l;
		$this->userSession = $userSession;
		$this->clientService = $clientService;
	}

	/**
	 * send federated share to a user of a public link
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param string $shareWith
	 * @param string $token
	 * @param string $password
	 * @return JSONResponse
	 */
	public function createFederatedShare($shareWith, $token, $password = '') {

		if (!$this->federatedShareProvider->isOutgoingServer2serverShareEnabled()) {
			return new JSONResponse(
				['message' => 'This server doesn\'t support outgoing federated shares'],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			list(, $server) = $this->addressHandler->splitUserRemote($shareWith);
			$share = $this->shareManager->getShareByToken($token);
		} catch (HintException $e) {
			return new JSONResponse(['message' => $e->getHint()], Http::STATUS_BAD_REQUEST);
		}

		// make sure that user is authenticated in case of a password protected link
		$storedPassword = $share->getPassword();
		$authenticated = $this->session->get('public_link_authenticated') === $share->getId() ||
			$this->shareManager->checkPassword($share, $password);
		if (!empty($storedPassword) && !$authenticated ) {
			return new JSONResponse(
				['message' => 'No permission to access the share'],
				Http::STATUS_BAD_REQUEST
			);
		}

		$share->setSharedWith($shareWith);

		try {
			$this->federatedShareProvider->create($share);
		} catch (\Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse(['remoteUrl' => $server]);
	}

	/**
	 * ask other server to get a federated share
	 *
	 * @NoAdminRequired
	 *
	 * @param string $token
	 * @param string $remote
	 * @param string $password
	 * @param string $owner (only for legacy reasons, can be removed with legacyMountPublicLink())
	 * @param string $ownerDisplayName (only for legacy reasons, can be removed with legacyMountPublicLink())
	 * @param string $name (only for legacy reasons, can be removed with legacyMountPublicLink())
	 * @return JSONResponse
	 */
	public function askForFederatedShare($token, $remote, $password = '', $owner = '', $ownerDisplayName = '', $name = '') {
		// check if server admin allows to mount public links from other servers
		if ($this->federatedShareProvider->isIncomingServer2serverShareEnabled() === false) {
			return new JSONResponse(['message' => $this->l->t('Server to server sharing is not enabled on this server')], Http::STATUS_BAD_REQUEST);
		}

		$shareWith = $this->userSession->getUser()->getUID() . '@' . $this->addressHandler->generateRemoteURL();

		$httpClient = $this->clientService->newClient();

		try {
			$response = $httpClient->post($remote . '/index.php/apps/federatedfilesharing/createFederatedShare',
				[
					'body' =>
						[
							'token' => $token,
							'shareWith' => rtrim($shareWith, '/'),
							'password' => $password
						],
					'connect_timeout' => 10,
				]
			);
		} catch (\Exception $e) {
			if (empty($password)) {
				$message = $this->l->t("Couldn't establish a federated share.");
			} else {
				$message = $this->l->t("Couldn't establish a federated share, maybe the password was wrong.");
			}
			return new JSONResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		}

		$body = $response->getBody();
		$result = json_decode($body, true);

		if (is_array($result) && isset($result['remoteUrl'])) {
			return new JSONResponse(['message' => $this->l->t('Federated Share request was successful, you will receive a invitation. Check your notifications.')]);
		}

		// if we doesn't get the expected response we assume that we try to add
		// a federated share from a Nextcloud <= 9 server
		return $this->legacyMountPublicLink($token, $remote, $password, $name, $owner, $ownerDisplayName);
	}

	/**
	 * Allow Nextcloud to mount a public link directly
	 *
	 * This code was copied from the apps/files_sharing/ajax/external.php with
	 * minimal changes, just to guarantee backward compatibility
	 *
	 * ToDo: Remove this method once Nextcloud 9 reaches end of life
	 *
	 * @param string $token
	 * @param string $remote
	 * @param string $password
	 * @param string $name
	 * @param string $owner
	 * @param string $ownerDisplayName
	 * @return JSONResponse
	 */
	private function legacyMountPublicLink($token, $remote, $password, $name, $owner, $ownerDisplayName) {

		// Check for invalid name
		if (!\OCP\Util::isValidFileName($name)) {
			return new JSONResponse(['message' => $this->l->t('The mountpoint name contains invalid characters.')], Http::STATUS_BAD_REQUEST);
		}
		$currentUser = $this->userSession->getUser()->getUID();
		$currentServer = $this->addressHandler->generateRemoteURL();
		if (\OC\Share\Helper::isSameUserOnSameServer($owner, $remote, $currentUser, $currentServer)) {
			return new JSONResponse(['message' => $this->l->t('Not allowed to create a federated share with the owner.')], Http::STATUS_BAD_REQUEST);
		}
		$discoveryManager = new \OCA\FederatedFileSharing\DiscoveryManager(
			\OC::$server->getMemCacheFactory(),
			\OC::$server->getHTTPClientService()
		);
		$externalManager = new \OCA\Files_Sharing\External\Manager(
			\OC::$server->getDatabaseConnection(),
			\OC\Files\Filesystem::getMountManager(),
			\OC\Files\Filesystem::getLoader(),
			\OC::$server->getHTTPHelper(),
			\OC::$server->getNotificationManager(),
			$discoveryManager,
			\OC::$server->getUserSession()->getUser()->getUID()
		);

		// check for ssl cert
		if (substr($remote, 0, 5) === 'https') {
			try {
				$client = $this->clientService->newClient();
				$client->get($remote, [
					'timeout' => 10,
					'connect_timeout' => 10,
				])->getBody();
			} catch (\Exception $e) {
				return new JSONResponse(['message' => $this->l->t('Invalid or untrusted SSL certificate')], Http::STATUS_BAD_REQUEST);
			}
		}
		$mount = $externalManager->addShare($remote, $token, $password, $name, $ownerDisplayName, true);
		/**
		 * @var \OCA\Files_Sharing\External\Storage $storage
		 */
		$storage = $mount->getStorage();
		try {
			// check if storage exists
			$storage->checkStorageAvailability();
		} catch (\OCP\Files\StorageInvalidException $e) {
			// note: checkStorageAvailability will already remove the invalid share
			\OCP\Util::writeLog(
				'federatedfilesharing',
				'Invalid remote storage: ' . get_class($e) . ': ' . $e->getMessage(),
				\OCP\Util::DEBUG
			);
			return new JSONResponse(['message' => $this->l->t('Could not authenticate to remote share, password might be wrong')], Http::STATUS_BAD_REQUEST);
		} catch (\Exception $e) {
			\OCP\Util::writeLog(
				'federatedfilesharing',
				'Invalid remote storage: ' . get_class($e) . ': ' . $e->getMessage(),
				\OCP\Util::DEBUG
			);
			$externalManager->removeShare($mount->getMountPoint());
			return new JSONResponse(['message' => $this->l->t('Storage not valid')], Http::STATUS_BAD_REQUEST);
		}
		$result = $storage->file_exists('');
		if ($result) {
			try {
				$storage->getScanner()->scanAll();
				return new JSONResponse(
					[
						'message' => $this->l->t('Federated Share successfully added'),
						'legacyMount' => '1'
					]
				);
			} catch (\OCP\Files\StorageInvalidException $e) {
				\OCP\Util::writeLog(
					'federatedfilesharing',
					'Invalid remote storage: ' . get_class($e) . ': ' . $e->getMessage(),
					\OCP\Util::DEBUG
				);
				return new JSONResponse(['message' => $this->l->t('Storage not valid')], Http::STATUS_BAD_REQUEST);
			} catch (\Exception $e) {
				\OCP\Util::writeLog(
					'federatedfilesharing',
					'Invalid remote storage: ' . get_class($e) . ': ' . $e->getMessage(),
					\OCP\Util::DEBUG
				);
				return new JSONResponse(['message' => $this->l->t('Couldn\'t add remote share')], Http::STATUS_BAD_REQUEST);
			}
		} else {
			$externalManager->removeShare($mount->getMountPoint());
			\OCP\Util::writeLog(
				'federatedfilesharing',
				'Couldn\'t add remote share',
				\OCP\Util::DEBUG
			);
			return new JSONResponse(['message' => $this->l->t('Couldn\'t add remote share')], Http::STATUS_BAD_REQUEST);
		}

	}

}
