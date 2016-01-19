<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCA\Files_External\Controller;

use OCA\Files_External\Lib\Auth\Password\UserProvided;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IUserSession;

class UserCredentialsController extends Controller {
	/**
	 * @var UserProvided
	 */
	private $authMechanism;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	public function __construct($appName, IRequest $request, UserProvided $authMechanism, IUserSession $userSession) {
		parent::__construct($appName, $request);
		$this->authMechanism = $authMechanism;
		$this->userSession = $userSession;
	}

	/**
	 * @param int $storageId
	 * @param string $username
	 * @param string $password
	 *
	 * @NoAdminRequired
	 */
	public function store($storageId, $username, $password) {
		$this->authMechanism->saveCredentials($this->userSession->getUser(), $storageId, $username, $password);
	}
}
