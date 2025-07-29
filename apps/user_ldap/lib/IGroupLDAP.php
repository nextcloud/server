<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP;

interface IGroupLDAP {

	//Used by LDAPProvider

	/**
	 * Return access for LDAP interaction.
	 * @param string $gid
	 * @return Access instance of Access for LDAP interaction
	 */
	public function getLDAPAccess($gid);

	/**
	 * Return a new LDAP connection for the specified group.
	 * @param string $gid
	 * @return \LDAP\Connection The LDAP connection
	 */
	public function getNewLDAPConnection($gid);
}
