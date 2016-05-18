<?php

/**
 * @author Christoph Wurst <christoph@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Settings\Controller;

use OC\Authentication\Token\IProvider;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserManager;

class AuthSettingsController extends Controller {

	/** @var IProvider */
	private $tokenProvider;

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/** @var string */
	private $uid;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IProvider $tokenProvider
	 * @param IUserManager $userManager
	 * @param string $uid
	 */
	public function __construct($appName, IRequest $request, IProvider $tokenProvider, IUserManager $userManager, $uid) {
		parent::__construct($appName, $request);
		$this->tokenProvider = $tokenProvider;
		$this->userManager = $userManager;
		$this->uid = $uid;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JSONResponse
	 */
	public function index() {
		$user = $this->userManager->get($this->uid);
		if (is_null($user)) {
			return [];
		}
		return $this->tokenProvider->getTokenByUser($user);
	}

}
