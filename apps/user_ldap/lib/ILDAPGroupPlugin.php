<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP;

/**
 * Implemented by the ldap_write_support to provide write operations from Nextcloud
 * to the LDAP server.
 */
interface ILDAPGroupPlugin {

	/**
	 * Check if plugin implements actions
	 * @return int
	 *
	 * Returns the supported actions as int to be
	 * compared with OC_GROUP_BACKEND_CREATE_GROUP etc.
	 */
	public function respondToActions();

	/**
	 * @param string $gid
	 * @return string|null The group DN if group creation was successful.
	 */
	public function createGroup(string $gid);

	/**
	 * delete a group
	 * @param string $gid gid of the group to delete
	 * @return bool
	 */
	public function deleteGroup(string $gid);

	/**
	 * Add a user to a group
	 * @param string $uid Name of the user to add to group
	 * @param string $gid Name of the group in which add the user
	 * @return bool
	 *
	 * Adds a user to a group.
	 */
	public function addToGroup(string $uid, string $gid);

	/**
	 * Removes a user from a group
	 * @param string $uid Name of the user to remove from group
	 * @param string $gid Name of the group from which remove the user
	 * @return bool
	 *
	 * removes the user from a group.
	 */
	public function removeFromGroup(string $uid, string $gid);
}
