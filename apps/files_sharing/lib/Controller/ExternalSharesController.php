<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Controller;

use OCA\Files_Sharing\External\Manager;
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
		private readonly Manager $externalManager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		return new JSONResponse($this->externalManager->getOpenShares());
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 */
	#[NoAdminRequired]
	public function create(string $id): JSONResponse {
		$externalShare = $this->externalManager->getShare($id);
		if ($externalShare !== false) {
			$this->externalManager->acceptShare($externalShare);
		}
		return new JSONResponse();
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 */
	#[NoAdminRequired]
	public function destroy(string $id): JSONResponse {
		$externalShare = $this->externalManager->getShare($id);
		if ($externalShare !== false) {
			$this->externalManager->declineShare($externalShare);
		}
		return new JSONResponse();
	}
}
