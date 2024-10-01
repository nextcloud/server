<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserManager;

class UserController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		protected IUserManager $userManager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Lookup user display names
	 *
	 * @param array $users
	 *
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/displaynames')]
	public function getDisplayNames($users) {
		$result = [];

		foreach ($users as $user) {
			$userObject = $this->userManager->get($user);
			if (is_object($userObject)) {
				$result[$user] = $userObject->getDisplayName();
			} else {
				$result[$user] = $user;
			}
		}

		$json = [
			'users' => $result,
			'status' => 'success'
		];

		return new JSONResponse($json);
	}
}
