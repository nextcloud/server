<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\LDAP;

/**
 * Interface ILDAPProvider
 *
 * @since 11.0.0
 */
interface ILDAPProvider {
	/**
	 * Translate a user id to LDAP DN.
	 * @param string $uid user id
	 * @return string
	 * @since 11.0.0
	 */
	public function getUserDN($uid);

	/**
	 * Translate a group id to LDAP DN.
	 * @param string $gid group id
	 * @return string
	 * @since 13.0.0
	 */
	public function getGroupDN($gid);

	/**
	 * Translate a LDAP DN to an internal user name.
	 * @param string $dn LDAP DN
	 * @return string with the internal user name
	 * @throws \Exception if translation was unsuccessful
	 * @since 11.0.0
	 */
	public function getUserName($dn);

	/**
	 * Convert a stored DN so it can be used as base parameter for LDAP queries.
	 * @param string $dn the DN
	 * @return string
	 * @since 11.0.0
	 */
	public function DNasBaseParameter($dn);

	/**
	 * Sanitize a DN received from the LDAP server.
	 * @param array|string $dn the DN in question
	 * @return array|string the sanitized DN
	 * @since 11.0.0
	 */
	public function sanitizeDN($dn);

	/**
	 * Return a new LDAP connection resource for the specified user.
	 * @param string $uid user id
	 * @return \LDAP\Connection|resource
	 * @since 11.0.0
	 */
	public function getLDAPConnection($uid);

	/**
	 * Return a new LDAP connection resource for the specified group.
	 * @param string $gid group id
	 * @return \LDAP\Connection|resource
	 * @since 13.0.0
	 */
	public function getGroupLDAPConnection($gid);

	/**
	 * Get the LDAP base for users.
	 * @param string $uid user id
	 * @return string the base for users
	 * @throws \Exception if user id was not found in LDAP
	 * @since 11.0.0
	 */
	public function getLDAPBaseUsers($uid);

	/**
	 * Get the LDAP base for groups.
	 * @param string $uid user id
	 * @return string the base for groups
	 * @throws \Exception if user id was not found in LDAP
	 * @since 11.0.0
	 */
	public function getLDAPBaseGroups($uid);

	/**
	 * Check whether a LDAP DN exists
	 * @param string $dn LDAP DN
	 * @return bool whether the DN exists
	 * @since 11.0.0
	 */
	public function dnExists($dn);

	/**
	 * Clear the cache if a cache is used, otherwise do nothing.
	 * @param string $uid user id
	 * @since 11.0.0
	 */
	public function clearCache($uid);

	/**
	 * Clear the cache if a cache is used, otherwise do nothing.
	 * @param string $gid group id
	 * @since 13.0.0
	 */
	public function clearGroupCache($gid);

	/**
	 * Get the LDAP attribute name for the user's display name
	 * @param string $uid user id
	 * @return string the display name field
	 * @throws \Exception if user id was not found in LDAP
	 * @since 12.0.0
	 */
	public function getLDAPDisplayNameField($uid);

	/**
	 * Get the LDAP attribute name for the email
	 * @param string $uid user id
	 * @return string the email field
	 * @throws \Exception if user id was not found in LDAP
	 * @since 12.0.0
	 */
	public function getLDAPEmailField($uid);

	/**
	 * Get the LDAP attribute name for the type of association between users and groups
	 * @param string $gid group id
	 * @return string the configuration, one of: 'memberUid', 'uniqueMember', 'member', 'gidNumber', ''
	 * @throws \Exception if group id was not found in LDAP
	 * @since 13.0.0
	 */
	public function getLDAPGroupMemberAssoc($gid);

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
