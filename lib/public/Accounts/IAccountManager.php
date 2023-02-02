<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Vincent Petry <vincent@nextcloud.com>
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

	/**
	 * The list of allowed scopes
	 *
	 * @since 25.0.0
	 */
	public const ALLOWED_SCOPES = [
		self::SCOPE_PRIVATE,
		self::SCOPE_LOCAL,
		self::SCOPE_FEDERATED,
		self::SCOPE_PUBLISHED,
		self::VISIBILITY_PRIVATE,
		self::VISIBILITY_CONTACTS_ONLY,
		self::VISIBILITY_PUBLIC,
	];

	public const PROPERTY_AVATAR = 'avatar';
	public const PROPERTY_DISPLAYNAME = 'displayname';
	public const PROPERTY_PHONE = 'phone';
	public const PROPERTY_EMAIL = 'email';
	public const PROPERTY_WEBSITE = 'website';
	public const PROPERTY_ADDRESS = 'address';
	public const PROPERTY_TWITTER = 'twitter';
	public const PROPERTY_FEDIVERSE = 'fediverse';

	/**
	 * @since 23.0.0
	 */
	public const PROPERTY_ORGANISATION = 'organisation';

	/**
	 * @since 23.0.0
	 */
	public const PROPERTY_ROLE = 'role';

	/**
	 * @since 23.0.0
	 */
	public const PROPERTY_HEADLINE = 'headline';

	/**
	 * @since 23.0.0
	 */
	public const PROPERTY_BIOGRAPHY = 'biography';

	/**
	 * @since 23.0.0
	 */
	public const PROPERTY_PROFILE_ENABLED = 'profile_enabled';

	/**
	 * The list of allowed properties
	 *
	 * @since 25.0.0
	 */
	public const ALLOWED_PROPERTIES = [
		self::PROPERTY_AVATAR,
		self::PROPERTY_DISPLAYNAME,
		self::PROPERTY_PHONE,
		self::PROPERTY_EMAIL,
		self::PROPERTY_WEBSITE,
		self::PROPERTY_ADDRESS,
		self::PROPERTY_TWITTER,
		self::PROPERTY_FEDIVERSE,
		self::PROPERTY_ORGANISATION,
		self::PROPERTY_ROLE,
		self::PROPERTY_HEADLINE,
		self::PROPERTY_BIOGRAPHY,
		self::PROPERTY_PROFILE_ENABLED,
	];

	public const COLLECTION_EMAIL = 'additional_mail';

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
	 * Update the account data with for the user
	 *
	 * @since 21.0.1
	 *
	 * @param IAccount $account
	 * @throws \InvalidArgumentException Message is the property that was invalid
	 */
	public function updateAccount(IAccount $account): void;

	/**
	 * Search for users based on account data
	 *
	 * @param string $property - property or property collection name – since
	 * NC 22 the implementation MAY add a fitting property collection into the
	 * search even if a property name was given e.g. email property and email
	 * collection)
	 * @param string[] $values
	 * @return array
	 *
	 * @since 21.0.0
	 */
	public function searchUsers(string $property, array $values): array;
}
