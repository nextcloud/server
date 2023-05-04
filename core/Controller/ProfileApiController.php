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

use OC\Core\Db\ProfileConfigMapper;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OC\Profile\ProfileManager;

class ProfileApiController extends OCSController {
	private ProfileConfigMapper $configMapper;
	private ProfileManager $profileManager;
	private IUserManager $userManager;
	private IUserSession $userSession;

	public function __construct(
		IRequest $request,
		ProfileConfigMapper $configMapper,
		ProfileManager $profileManager,
		IUserManager $userManager,
		IUserSession $userSession
	) {
		parent::__construct('core', $request);
		$this->configMapper = $configMapper;
		$this->profileManager = $profileManager;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @PasswordConfirmationRequired
	 * @UserRateThrottle(limit=40, period=600)
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
