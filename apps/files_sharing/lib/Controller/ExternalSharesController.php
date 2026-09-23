<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing\Controller;

use OCA\Files_Sharing\BackgroundJob\ExternalShareScanJob;
use OCA\Files_Sharing\External\Manager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\BackgroundJob\IJobList;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use RuntimeException;

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
		private readonly IJobList $jobList,
		private readonly IUserSession $userSession,
	) {
		parent::__construct($appName, $request);
	}

	private function getUser(): IUser {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new RuntimeException('No user for non-public page');
		}
		return $user;
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		return new JSONResponse($this->externalManager->getOpenShares($this->getUser()));
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 */
	#[NoAdminRequired]
	public function create(string $id): JSONResponse {
		$externalShare = $this->externalManager->getShare($id, $this->getUser());
		if ($externalShare !== false) {
			$this->externalManager->acceptShare($externalShare, $this->getUser());
			$this->jobList->add(ExternalShareScanJob::class, [$externalShare->getUser(), $externalShare->getMountpoint()]);
		}
		return new JSONResponse();
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 */
	#[NoAdminRequired]
	public function destroy(string $id): JSONResponse {
		$externalShare = $this->externalManager->getShare($id, $this->getUser());
		if ($externalShare !== false) {
			$this->externalManager->declineShare($externalShare, $this->getUser());
		}
		return new JSONResponse();
	}
}
