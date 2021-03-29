<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Accounts;

use OCP\IUser;

/**
 * Access user profile information
 *
 * @since 15.0.0
 *
 */
interface IAccountManager {

	/**
	 * Contact details visible locally only
	 *
	 * @since 21.0.1
	 */
	public const SCOPE_PRIVATE = 'v2-private';

	/**
	 * Contact details visible locally and through public link access on local instance
	 *
	 * @since 21.0.1
	 */
	public const SCOPE_LOCAL = 'v2-local';

	/**
	 * Contact details visible locally, through public link access and on trusted federated servers.
	 *
	 * @since 21.0.1
	 */
	public const SCOPE_FEDERATED = 'v2-federated';

	/**
	 * Contact details visible locally, through public link access, on trusted federated servers
	 * and published to the public lookup server.
	 *
	 * @since 21.0.1
	 */
	public const SCOPE_PUBLISHED = 'v2-published';

	/**
	 * Contact details only visible locally
	 *
	 * @deprecated 21.0.1
	 */
	public const VISIBILITY_PRIVATE = 'private';

	/**
	 * Contact details visible on trusted federated servers.
	 *
	 * @deprecated 21.0.1
	 */
	public const VISIBILITY_CONTACTS_ONLY = 'contacts';

	/**
	 * Contact details visible on trusted federated servers and in the public lookup server.
	 *
	 * @deprecated 21.0.1
	 */
	public const VISIBILITY_PUBLIC = 'public';

	public const PROPERTY_AVATAR = 'avatar';
	public const PROPERTY_DISPLAYNAME = 'displayname';
	public const PROPERTY_PHONE = 'phone';
	public const PROPERTY_EMAIL = 'email';
	public const PROPERTY_WEBSITE = 'website';
	public const PROPERTY_ADDRESS = 'address';
	public const PROPERTY_TWITTER = 'twitter';

	public const NOT_VERIFIED = '0';
	public const VERIFICATION_IN_PROGRESS = '1';
	public const VERIFIED = '2';

	/**
	 * Get the account data for a given user
	 *
	 * @since 15.0.0
	 *
	 * @param IUser $user
	 * @return IAccount
	 */
	public function getAccount(IUser $user): IAccount;

	/**
	 * Search for users based on account data
	 *
	 * @param string $property
	 * @param string[] $values
	 * @return array
	 *
	 * @since 21.0.0
	 */
	public function searchUsers(string $property, array $values): array;
}
