<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Controller;

use OCA\Files_Sharing\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class AcceptController extends Controller {

	public function __construct(
		IRequest $request,
		private ShareManager $shareManager,
		private IUserSession $userSession,
		private IURLGenerator $urlGenerator,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function accept(string $shareId): Response {
		try {
			$share = $this->shareManager->getShareById($shareId);
		} catch (ShareNotFound $e) {
			return new NotFoundResponse();
		}

		$user = $this->userSession->getUser();
		if ($user === null) {
			return new NotFoundResponse();
		}

		try {
			$share = $this->shareManager->acceptShare($share, $user->getUID());
		} catch (\Exception $e) {
			// Just ignore
		}

		$url = $this->urlGenerator->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $share->getNode()->getId()]);

		return new RedirectResponse($url);
	}
}
