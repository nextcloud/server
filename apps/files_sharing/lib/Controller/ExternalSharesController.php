<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class ExternalSharesController
 *
 * @package OCA\Files_Sharing\Controller
 */
class ExternalSharesController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private \OCA\Files_Sharing\External\Manager $externalManager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 *
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function index() {
		return new JSONResponse($this->externalManager->getOpenShares());
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function create($id) {
		$this->externalManager->acceptShare($id);
		return new JSONResponse();
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 *
	 * @param integer $id
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function destroy($id) {
		$this->externalManager->declineShare($id);
		return new JSONResponse();
	}
}
