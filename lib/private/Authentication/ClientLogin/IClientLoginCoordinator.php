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

namespace OC\Authentication\ClientLogin;

use OC\Authentication\Exceptions\ClientLoginPendingException;
use OC\Authentication\Exceptions\InvalidAccessTokenException;
use OCP\IUser;

interface IClientLoginCoordinator {

	/**
	 * @param string $name client name
	 * @return string new access token to identify async login process
	 */
	public function startClientLogin($name);

	/**
	 * @param type $accessToken
	 * @throws InvalidAccessTokenException
	 */
	public function finishClientLogin($accessToken, IUser $user);

	/**
	 * @param string $accessToken
	 * @throws InvalidAccessTokenException
	 * @throws ClientLoginPendingException
	 * @return string
	 */
	public function getClientToken($accessToken);
}
