<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Controller;

use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\Service\CheckUserService;
use OCA\User_LDAP\Settings\Admin;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\Server;

class APIController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly CheckUserService $checkUserService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Check a user and update its attribute in the LDAP server
	 *
	 * @param string $userID ID of the user
	 * @return DataResponse<Http::STATUS_OK, array{exists:bool, wasMapped: bool, attributes?: array<string, string>}, array{}>
	 * @throws OCSException An unexpected error happened
	 * @throws OCSForbiddenException Cannot check user existence
	 *
	 * 200: The user configuration as found in the LDAP server
	 */
	#[ApiRoute(verb: 'POST', url: '/api/v1/checkUser/{userID}')]
	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function checkUser(string $userID): DataResponse {
		if ($this->checkUserService->assertAllowed(false)) {
			throw new OCSForbiddenException('Cannot check user existence, because disabled LDAP configurations are present.');
		}

		$result = $this->checkUserService->checkUser($userID, true);
		return new DataResponse($result);
	}
}
