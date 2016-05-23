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
use OCP\Share\IManager;

class SaveToOwnCloudController extends Controller {

	/** @var FederatedShareProvider */
	private $federatedShareProvider;

	/** @var AddressHandler */
	private $addressHandler;

	/** @var IManager  */
	private $shareManager;

	public function __construct($appName,
								IRequest $request,
								FederatedShareProvider $federatedShareProvider,
								IManager $shareManager,
								AddressHandler $addressHandler) {
		parent::__construct($appName, $request);

		$this->federatedShareProvider = $federatedShareProvider;
		$this->shareManager = $shareManager;
		$this->addressHandler = $addressHandler;
	}

	/**
	 * save public link to my ownCloud by asking the owner to create a federated
	 * share with me
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param string $shareWith
	 * @param string $token
	 * @return JSONResponse
	 */
	public function saveToOwnCloud($shareWith, $token) {

		try {
			list(, $server) = $this->addressHandler->splitUserRemote($shareWith);
			$share = $this->shareManager->getShareByToken($token);
		} catch (HintException $e) {
			return new JSONResponse(['message' => $e->getHint()], Http::STATUS_BAD_REQUEST);
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
