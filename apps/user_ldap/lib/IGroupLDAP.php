<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP;

/**
 * Interface defining methods used by the LDAPProvider
 */
interface IGroupLDAP {
	/**
	 * Return access for LDAP interaction.
	 *
	 * @return Access instance of Access for LDAP interaction
	 */
	public function getLDAPAccess(string $name): Access;

	/**
	 * Return a new LDAP connection for the specified group.
	 *
	 * @return \LDAP\Connection The LDAP connection
	 */
	public function getNewLDAPConnection(string $name): \LDAP\Connection;
}
