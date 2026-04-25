<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\LDAP;

use LDAP\Connection;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Interface ILDAPProvider
 *
 * @since 11.0.0
 */
#[Consumable(since: '11.0.0')]
interface ILDAPProvider {
	/**
	 * Translate a user id to LDAP DN.
	 * @param string $uid user id
	 * @since 11.0.0
	 */
	public function getUserDN(string $uid): string;

	/**
	 * Translate a group id to LDAP DN.
	 * @param string $gid group id
	 * @since 13.0.0
	 */
	public function getGroupDN(string $gid): string;

	/**
	 * Translate a LDAP DN to an internal user name.
	 * @param string $dn LDAP DN
	 * @return string with the internal user name
	 * @throws \Exception if translation was unsuccessful
	 * @since 11.0.0
	 */
	public function getUserName(string $dn): string;

	/**
	 * Convert a stored DN so it can be used as base parameter for LDAP queries.
	 * @param string $dn the DN
	 * @return string
	 * @since 11.0.0
	 */
	public function DNasBaseParameter(string $dn): string;

	/**
	 * Sanitize a DN received from the LDAP server.
	 * @param array|string $dn the DN in question
	 * @return array|string the sanitized DN
	 * @since 11.0.0
	 */
	public function sanitizeDN(array|string $dn): array|string;

	/**
	 * Return a new LDAP connection resource for the specified user.
	 * @param string $uid user id
	 * @since 11.0.0
	 */
	public function getLDAPConnection(string $uid): Connection;

	/**
	 * Return a new LDAP connection resource for the specified group.
	 * @param string $gid group id
	 * @since 13.0.0
	 */
	public function getGroupLDAPConnection(string $gid): Connection;

	/**
	 * Get the LDAP base for users.
	 * @param string $uid user id
	 * @return string the base for users
	 * @throws \Exception if user id was not found in LDAP
	 * @since 11.0.0
	 */
	public function getLDAPBaseUsers(string $uid): string;

	/**
	 * Get the LDAP base for groups.
	 * @param string $uid user id
	 * @return string the base for groups
	 * @throws \Exception if user id was not found in LDAP
	 * @since 11.0.0
	 */
	public function getLDAPBaseGroups(string $uid): string;

	/**
	 * Check whether a LDAP DN exists
	 * @param string $dn LDAP DN
	 * @return bool whether the DN exists
	 * @since 11.0.0
	 */
	public function dnExists(string $dn): bool;

	/**
	 * Clear the cache if a cache is used, otherwise do nothing.
	 * @param string $uid user id
	 * @since 11.0.0
	 */
	public function clearCache(string $uid): void;

	/**
	 * Clear the cache if a cache is used, otherwise do nothing.
	 * @param string $gid group id
	 * @since 13.0.0
	 */
	public function clearGroupCache(string $gid): void;

	/**
	 * Get the LDAP attribute name for the user's display name
	 * @param string $uid user id
	 * @return string the display name field
	 * @throws \Exception if user id was not found in LDAP
	 * @since 12.0.0
	 */
	public function getLDAPDisplayNameField(string $uid): string;

	/**
	 * Get the LDAP attribute name for the email
	 * @param string $uid user id
	 * @return string the email field
	 * @throws \Exception if user id was not found in LDAP
	 * @since 12.0.0
	 */
	public function getLDAPEmailField(string $uid): string;

	/**
	 * Get the LDAP attribute name for the type of association between users and groups
	 * @param string $gid group id
	 * @return string the configuration, one of: 'memberUid', 'uniqueMember', 'member', 'gidNumber', ''
	 * @throws \Exception if group id was not found in LDAP
	 * @since 13.0.0
	 */
	public function getLDAPGroupMemberAssoc(string $gid): string;

	/**
	 * Get an LDAP attribute for a nextcloud user
	 *
	 * @throws \Exception if user id was not found in LDAP
	 * @since 21.0.0
	 */
	public function getUserAttribute(string $uid, string $attribute): ?string;

	/**
	 * Get a multi-value LDAP attribute for a nextcloud user
	 *
	 * @throws \Exception if user id was not found in LDAP
	 * @since 22.0.0
	 */
	public function getMultiValueUserAttribute(string $uid, string $attribute): array;
}
