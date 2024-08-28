<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OC\Profile\ProfileManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Profile\BeforeTemplateRenderedEvent;
use OCP\Share\IManager as IShareManager;
use OCP\UserStatus\IManager as IUserStatusManager;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ProfilePageController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IInitialState $initialStateService,
		private ProfileManager $profileManager,
		private IShareManager $shareManager,
		private IUserManager $userManager,
		private IUserSession $userSession,
		private IUserStatusManager $userStatusManager,
		private INavigationManager $navigationManager,
		private IEventDispatcher $eventDispatcher,
	) {
		parent::__construct($appName, $request);
	}

	#[PublicPage]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/u/{targetUserId}')]
	#[BruteForceProtection(action: 'user')]
	#[UserRateLimit(limit: 30, period: 120)]
	#[AnonRateLimit(limit: 30, period: 120)]
	public function index(string $targetUserId): TemplateResponse {
		$profileNotFoundTemplate = new TemplateResponse(
			'core',
			'404-profile',
			[],
			TemplateResponse::RENDER_AS_GUEST,
		);

		$targetUser = $this->userManager->get($targetUserId);
		if ($targetUser === null) {
			$profileNotFoundTemplate->throttle();
			return $profileNotFoundTemplate;
		}
		if (!$targetUser->isEnabled()) {
			return $profileNotFoundTemplate;
		}
		$visitingUser = $this->userSession->getUser();

		if (!$this->profileManager->isProfileEnabled($targetUser)) {
			return $profileNotFoundTemplate;
		}

		// Run user enumeration checks only if viewing another user's profile
		if ($targetUser !== $visitingUser) {
			if (!$this->shareManager->currentUserCanEnumerateTargetUser($visitingUser, $targetUser)) {
				return $profileNotFoundTemplate;
			}
		}

		if ($visitingUser !== null) {
			$userStatuses = $this->userStatusManager->getUserStatuses([$targetUserId]);
			$status = $userStatuses[$targetUserId] ?? null;
			if ($status !== null) {
				$this->initialStateService->provideInitialState('status', [
					'icon' => $status->getIcon(),
					'message' => $status->getMessage(),
				]);
			}
		}

		$this->initialStateService->provideInitialState(
			'profileParameters',
			$this->profileManager->getProfileFields($targetUser, $visitingUser),
		);

		if ($targetUser === $visitingUser) {
			$this->navigationManager->setActiveEntry('profile');
		}

		$this->eventDispatcher->dispatchTyped(new BeforeTemplateRenderedEvent($targetUserId));

		\OCP\Util::addScript('core', 'profile');

		return new TemplateResponse(
			'core',
			'profile',
			[],
			$this->userSession->isLoggedIn() ? TemplateResponse::RENDER_AS_USER : TemplateResponse::RENDER_AS_PUBLIC,
		);
	}
}
