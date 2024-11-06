<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OC\Core\Db\ProfileConfigMapper;
use OC\Profile\ProfileManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;

class ProfileApiController extends OCSController {
	public function __construct(
		IRequest $request,
		private ProfileConfigMapper $configMapper,
		private ProfileManager $profileManager,
		private IUserManager $userManager,
		private IUserSession $userSession,
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
		$targetUser = $this->userManager->get($targetUserId);

		if (!$this->userManager->userExists($targetUserId)) {
			throw new OCSNotFoundException('Account does not exist');
		}

		if ($requestingUser !== $targetUser) {
			throw new OCSForbiddenException('People can only edit their own visibility settings');
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
}
