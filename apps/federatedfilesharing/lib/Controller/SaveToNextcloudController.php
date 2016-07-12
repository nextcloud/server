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
use OCP\IRequest;
use OCP\ISession;
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

	/**
	 * SaveToNextcloudController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param FederatedShareProvider $federatedShareProvider
	 * @param IManager $shareManager
	 * @param AddressHandler $addressHandler
	 * @param ISession $session
	 */
	public function __construct($appName,
								   IRequest $request,
								   FederatedShareProvider $federatedShareProvider,
								   IManager $shareManager,
								   AddressHandler $addressHandler,
								   ISession $session
	) {
		parent::__construct($appName, $request);

		$this->federatedShareProvider = $federatedShareProvider;
		$this->shareManager = $shareManager;
		$this->addressHandler = $addressHandler;
		$this->session = $session;
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

}
