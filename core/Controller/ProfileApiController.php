<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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

use OC\Core\Db\ProfileConfigMapper;
use OC\Profile\ProfileManager;
use OCP\AppFramework\Http;
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
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @PasswordConfirmationRequired
	 * @UserRateThrottle(limit=40, period=600)
	 *
	 * Update the visibility of a parameter
	 *
	 * @param string $targetUserId ID of the user
	 * @param string $paramId ID of the parameter
	 * @param string $visibility New visibility
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSBadRequestException Updating visibility is not possible
	 * @throws OCSForbiddenException Not allowed to edit other users visibility
	 * @throws OCSNotFoundException User not found
	 *
	 * 200: Visibility updated successfully
	 */
	public function setVisibility(string $targetUserId, string $paramId, string $visibility): DataResponse {
		$requestingUser = $this->userSession->getUser();
		$targetUser = $this->userManager->get($targetUserId);

		if (!$this->userManager->userExists($targetUserId)) {
			throw new OCSNotFoundException('User does not exist');
		}

		if ($requestingUser !== $targetUser) {
			throw new OCSForbiddenException('Users can only edit their own visibility settings');
		}

		// Ensure that a profile config is created in the database
		$this->profileManager->getProfileConfig($targetUser, $targetUser);
		$config = $this->configMapper->get($targetUserId);

		if (!in_array($paramId, array_keys($config->getVisibilityMap()), true)) {
			throw new OCSBadRequestException('User does not have a profile parameter with ID: ' . $paramId);
		}

		$config->setVisibility($paramId, $visibility);
		$this->configMapper->update($config);

		return new DataResponse();
	}
}
