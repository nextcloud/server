<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@owncloud.com>
 *
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

namespace OCP\Authentication\TwoFactorAuth;

use OCP\IUser;
use OCP\Template;

/**
 * @since 9.1.0
 */
interface IProvider {

	/**
	 * Get unique identifier of this 2FA provider
	 *
	 * @since 9.1.0
	 *
	 * @return string
	 */
	public function getId();

	/**
	 * Get the display name for selecting the 2FA provider
	 *
	 * Example: "Email"
	 *
	 * @since 9.1.0
	 *
	 * @return string
	 */
	public function getDisplayName();

	/**
	 * Get the description for selecting the 2FA provider
	 *
	 * Example: "Get a token via e-mail"
	 *
	 * @since 9.1.0
	 *
	 * @return string
	 */
	public function getDescription();

	/**
	 * Get the template for rending the 2FA provider view
	 *
	 * @since 9.1.0
	 *
	 * @param IUser $user
	 * @return Template
	 */
	public function getTemplate(IUser $user);

	/**
	 * Verify the given challenge
	 *
	 * @since 9.1.0
	 *
	 * @param IUser $user
	 * @param string $challenge
	 */
	public function verifyChallenge(IUser $user, $challenge);

	/**
	 * Decides whether 2FA is enabled for the given user
	 *
	 * @since 9.1.0
	 *
	 * @param IUser $user
	 * @return boolean
	 */
	public function isTwoFactorAuthEnabledForUser(IUser $user);
}
