<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Controller;

use OC\Profile\ProfileManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use OCP\UserStatus\IManager as IUserStatusManager;

class ProfilePageController extends Controller {

	/** @var IInitialState */
	private $initialStateService;

	/** @var ProfileManager */
	private $profileManager;

	/** @var IShareManager */
	private $shareManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IUserSession */
	private $userSession;

	/** @var IUserStatusManager */
	private $userStatusManager;

	public function __construct(
		$appName,
		IRequest $request,
		IInitialState $initialStateService,
		ProfileManager $profileManager,
		IShareManager $shareManager,
		IUserManager $userManager,
		IUserSession $userSession,
		IUserStatusManager $userStatusManager
	) {
		parent::__construct($appName, $request);
		$this->initialStateService = $initialStateService;
		$this->profileManager = $profileManager;
		$this->shareManager = $shareManager;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->userStatusManager = $userStatusManager;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @BruteForceProtection(action=user)
	 * @UserRateThrottle(limit=30, period=120)
	 * @AnonRateThrottle(limit=30, period=120)
	 */
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
			$this->profileManager->getProfileParams($targetUser, $visitingUser),
		);

		\OCP\Util::addScript('core', 'profile');

		return new TemplateResponse(
			'core',
			'profile',
			[],
			$this->userSession->isLoggedIn() ? TemplateResponse::RENDER_AS_USER : TemplateResponse::RENDER_AS_PUBLIC,
		);
	}
}
