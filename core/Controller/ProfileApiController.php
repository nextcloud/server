<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OC\Core\Db\ProfileConfigMapper;
use OC\Core\ResponseDefinitions;
use OC\Profile\ProfileManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager;

/**
 * @psalm-import-type CoreProfileData from ResponseDefinitions
 */
class ProfileApiController extends OCSController {
	public function __construct(
		IRequest $request,
		private IConfig $config,
		private ITimeFactory $timeFactory,
		private ProfileConfigMapper $configMapper,
		private ProfileManager $profileManager,
		private IUserManager $userManager,
		private IUserSession $userSession,
		private IManager $shareManager,
	) {
		parent::__construct('core', $request);
	}

	/**
	 * @NoSubAdminRequired
	 *
	 * Update the visibility of a parameter
	 *
	 * @param string $targetUserId ID of the user
	 * @param string $paramId ID of the parameter
	 * @param string $visibility New visibility
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSBadRequestException Updating visibility is not possible
	 * @throws OCSForbiddenException Not allowed to edit other users visibility
	 * @throws OCSNotFoundException Account not found
	 *
	 * 200: Visibility updated successfully
	 */
	#[NoAdminRequired]
	#[PasswordConfirmationRequired]
	#[UserRateLimit(limit: 40, period: 600)]
	#[ApiRoute(verb: 'PUT', url: '/{targetUserId}', root: '/profile')]
	public function setVisibility(string $targetUserId, string $paramId, string $visibility): DataResponse {
		$requestingUser = $this->userSession->getUser();
		if ($requestingUser->getUID() !== $targetUserId) {
			throw new OCSForbiddenException('People can only edit their own visibility settings');
		}

		$targetUser = $this->userManager->get($targetUserId);
		if (!$targetUser instanceof IUser) {
			throw new OCSNotFoundException('Account does not exist');
		}

		// Ensure that a profile config is created in the database
		$this->profileManager->getProfileConfig($targetUser, $targetUser);
		$config = $this->configMapper->get($targetUserId);

		if (!in_array($paramId, array_keys($config->getVisibilityMap()), true)) {
			throw new OCSBadRequestException('Account does not have a profile parameter with ID: ' . $paramId);
		}

		$config->setVisibility($paramId, $visibility);
		$this->configMapper->update($config);

		return new DataResponse();
	}

	/**
	 * Get profile fields for another user
	 *
	 * @param string $targetUserId ID of the user
	 * @return DataResponse<Http::STATUS_OK, CoreProfileData, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: Profile data returned successfully
	 * 400: Profile is disabled
	 * 404: Account not found or disabled
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/{targetUserId}', root: '/profile')]
	#[BruteForceProtection(action: 'user')]
	#[UserRateLimit(limit: 30, period: 120)]
	public function getProfileFields(string $targetUserId): DataResponse {
		$targetUser = $this->userManager->get($targetUserId);
		if (!$targetUser instanceof IUser) {
			$response = new DataResponse(null, Http::STATUS_NOT_FOUND);
			$response->throttle();
			return $response;
		}
		if (!$targetUser->isEnabled()) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}

		if (!$this->profileManager->isProfileEnabled($targetUser)) {
			return new DataResponse(null, Http::STATUS_BAD_REQUEST);
		}

		$requestingUser = $this->userSession->getUser();
		if ($targetUser !== $requestingUser) {
			if (!$this->shareManager->currentUserCanEnumerateTargetUser($requestingUser, $targetUser)) {
				return new DataResponse(null, Http::STATUS_NOT_FOUND);
			}
		}

		$profileFields = $this->profileManager->getProfileFields($targetUser, $requestingUser);

		// Extend the profile information with timezone of the user
		$timezoneStringTarget = $this->config->getUserValue($targetUser->getUID(), 'core', 'timezone') ?: $this->config->getSystemValueString('default_timezone', 'UTC');
		try {
			$timezoneTarget = new \DateTimeZone($timezoneStringTarget);
		} catch (\Throwable) {
			$timezoneTarget = new \DateTimeZone('UTC');
		}
		$profileFields['timezone'] = $timezoneTarget->getName(); // E.g. Europe/Berlin
		$profileFields['timezoneOffset'] = $timezoneTarget->getOffset($this->timeFactory->now()); // In seconds E.g. 7200

		return new DataResponse($profileFields);
	}
}
