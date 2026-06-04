<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP;

use OCP\LDAP\Exceptions\MultipleUsersReturnedException;

interface IUserLDAP {

	//Functions used by LDAPProvider

	/**
	 * Return access for LDAP interaction.
	 * @param string $uid
	 * @return Access instance of Access for LDAP interaction
	 */
	public function getLDAPAccess($uid);

	/**
	 * Return a new LDAP connection for the specified user.
	 * @param string $uid
	 * @return \LDAP\Connection of the LDAP connection
	 */
	public function getNewLDAPConnection($uid);

	/**
	 * Return the username for the given LDAP DN, if available.
	 * @param string $dn
	 * @return string|false with the username
	 */
	public function dn2UserName($dn);

	/**
	 * Fetches one user from LDAP based on a filter or a custom attribute and search term.
	 *
	 * @param string $attribute The LDAP attribute name to search against (e.g., 'mail', 'cn', 'uid').
	 * @param string $searchTerm The search term to match against the attribute. Will be escaped for LDAP filter safety.
	 * @return string|null Returns the username if found in LDAP using the configured LDAP filter, or null if no user is found.
	 * @throws MultipleUsersReturnedException if multiple users have been found (search query should not allow this)
	 */
	public function getUserFromCustomAttribute(string $attribute, string $searchTerm): ?string;
}
