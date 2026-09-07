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
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\NotFoundException;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IShare;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class AcceptController extends Controller {

	public function __construct(
		IRequest $request,
		private ShareManager $shareManager,
		private IUserSession $userSession,
		private IURLGenerator $urlGenerator,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function showAccept(string $shareId): Response {
		try {
			$share = $this->shareManager->getShareById($shareId);
		} catch (ShareNotFound $e) {
			return new NotFoundResponse();
		}

		$user = $this->userSession->getUser();
		if ($user === null || !$this->isRecipient($share, $user)) {
			return new NotFoundResponse();
		}

		try {
			$filename = $share->getNode()->getName();
		} catch (NotFoundException) {
			return new NotFoundResponse();
		}

		$sharer = $this->userManager->get($share->getSharedBy());
		$sharerDisplayName = $sharer !== null ? $sharer->getDisplayName() : $share->getSharedBy();

		return new TemplateResponse(
			Application::APP_ID,
			'accept-share',
			[
				'filename' => $filename,
				'sharerDisplayName' => $sharerDisplayName,
			],
			TemplateResponse::RENDER_AS_GUEST,
		);
	}

	#[NoAdminRequired]
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
			return new NotFoundResponse();
		}

		$url = $this->urlGenerator->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $share->getNode()->getId()]);

		return new RedirectResponse($url);
	}

	private function isRecipient(IShare $share, IUser $user): bool {
		if ($share->getShareType() === IShare::TYPE_USER) {
			return $share->getSharedWith() === $user->getUID();
		}

		if ($share->getShareType() === IShare::TYPE_GROUP) {
			$group = $this->groupManager->get($share->getSharedWith());
			return $group !== null && $group->inGroup($user);
		}

		return false;
	}
}
