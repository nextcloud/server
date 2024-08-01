<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP;

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
}
