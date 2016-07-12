<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
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

class SaveToNextcloudController extends Controller {

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
	 * SaveToNextcloudController constructor.
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
	 * save public link to my Nextcloud by asking the owner to create a federated
	 * share with me
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param string $shareWith
	 * @param string $token
	 * @param string $password
	 * @return JSONResponse
	 */
	public function saveToNextcloud($shareWith, $token, $password = '') {

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
			return new JSONResponse(['message' => 'No permission to access the share'], Http::STATUS_BAD_REQUEST);
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
	 * @return JSONResponse
	 */
	public function askForFederatedShare($token, $remote, $password = '') {
		// check if server admin allows to mount public links from other servers
		if ($this->federatedShareProvider->isIncomingServer2serverShareEnabled() === false) {
			return new JSONResponse(['message' => $this->l->t('Server to server sharing is not enabled on this server')], Http::STATUS_BAD_REQUEST);
		}

		$shareWith = $this->userSession->getUser()->getUID() . '@' . $this->addressHandler->generateRemoteURL();

		$httpClient = $this->clientService->newClient();

		try {
			$httpClient->post($remote . '/index.php/apps/federatedfilesharing/saveToNextcloud',
				[
					'body' =>
						[
							'token' => $token,
							'shareWith' => rtrim($shareWith, '/'),
							'password' => $password
						]
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

		return new JSONResponse(['message' => $this->l->t('Federated Share request was successful, you will receive a invitation. Check your notifications.')]);

	}

}
