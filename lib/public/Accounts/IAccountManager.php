<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	 * @since 15.0.0
	 * @deprecated 21.0.1
	 */
	public const VISIBILITY_PRIVATE = 'private';

	/**
	 * Contact details visible on trusted federated servers.
	 *
	 * @since 15.0.0
	 * @deprecated 21.0.1
	 */
	public const VISIBILITY_CONTACTS_ONLY = 'contacts';

	/**
	 * Contact details visible on trusted federated servers and in the public lookup server.
	 *
	 * @since 15.0.0
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

	/**
	 * @since 15.0.0
	 */
	public const PROPERTY_AVATAR = 'avatar';

	/**
	 * @since 15.0.0
	 */
	public const PROPERTY_DISPLAYNAME = 'displayname';

	/**
	 * @since 27.0.0
	 */
	public const PROPERTY_DISPLAYNAME_LEGACY = 'display-name';

	/**
	 * @since 15.0.0
	 */
	public const PROPERTY_PHONE = 'phone';

	/**
	 * @since 15.0.0
	 */
	public const PROPERTY_EMAIL = 'email';

	/**
	 * @since 15.0.0
	 */
	public const PROPERTY_WEBSITE = 'website';

	/**
	 * @since 15.0.0
	 */
	public const PROPERTY_ADDRESS = 'address';

	/**
	 * @since 15.0.0
	 */
	public const PROPERTY_TWITTER = 'twitter';

	/**
	 * @since 26.0.0
	 */
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
	 * @since 30.0.0
	 */
	public const PROPERTY_BIRTHDATE = 'birthdate';

	/**
	 * @since 31.0.0
	 */
	public const PROPERTY_PRONOUNS = 'pronouns';

	/**
	 * The list of allowed properties
	 *
	 * @since 25.0.0
	 */
	public const ALLOWED_PROPERTIES = [
		self::PROPERTY_ADDRESS,
		self::PROPERTY_AVATAR,
		self::PROPERTY_BIOGRAPHY,
		self::PROPERTY_BIRTHDATE,
		self::PROPERTY_DISPLAYNAME,
		self::PROPERTY_EMAIL,
		self::PROPERTY_FEDIVERSE,
		self::PROPERTY_HEADLINE,
		self::PROPERTY_ORGANISATION,
		self::PROPERTY_PHONE,
		self::PROPERTY_PROFILE_ENABLED,
		self::PROPERTY_PRONOUNS,
		self::PROPERTY_ROLE,
		self::PROPERTY_TWITTER,
		self::PROPERTY_WEBSITE,
	];


	/**
	 * @since 22.0.0
	 */
	public const COLLECTION_EMAIL = 'additional_mail';

	/**
	 * @since 15.0.0
	 */
	public const NOT_VERIFIED = '0';

	/**
	 * @since 15.0.0
	 */
	public const VERIFICATION_IN_PROGRESS = '1';

	/**
	 * @since 15.0.0
	 */
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
	 *                         NC 22 the implementation MAY add a fitting property collection into the
	 *                         search even if a property name was given e.g. email property and email
	 *                         collection)
	 * @param string[] $values
	 * @return array
	 *
	 * @since 21.0.0
	 */
	public function searchUsers(string $property, array $values): array;
}
