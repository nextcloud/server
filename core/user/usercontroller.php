<?php
/**
 * Copyright (c) 2014 Jörn Dreyer <jfd@owncloud.com>
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\User;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\IRequest;

class UserController extends Controller {
	/**
	 * @var \OCP\IUserManager
	 */
	protected $userManager;

	/**
	 * @var \OC_Defaults
	 */
	protected $defaults;

	public function __construct($appName,
								IRequest $request,
								$userManager,
								$defaults
	) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->defaults = $defaults;
	}

	/**
	 * Lookup user display names
	 *
	 * @NoAdminRequired
	 *
	 * @param array $users
	 *
	 * @return JSONResponse
	 */
	public function getDisplayNames($users) {
		$result = array();

		foreach ($users as $user) {
			$userObject = $this->userManager->get($user);
			if (is_object($userObject)) {
				$result[$user] = $userObject->getDisplayName();
			} else {
				$result[$user] = $user;
			}
		}

		$json = array(
			'users' => $result,
			'status' => 'success'
		);

		return new JSONResponse($json);

	}
}
