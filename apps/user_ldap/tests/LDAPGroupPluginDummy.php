<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\ILDAPGroupPlugin;

class LDAPGroupPluginDummy implements ILDAPGroupPlugin {
	public function respondToActions() {
		return null;
	}

	public function createGroup($gid) {
		return null;
	}

	public function deleteGroup($gid) {
		return null;
	}

	public function addToGroup($uid, $gid) {
		return null;
	}

	public function removeFromGroup($uid, $gid) {
		return null;
	}

	public function countUsersInGroup($gid, $search = '') {
		return null;
	}

	public function getGroupDetails($gid) {
		return null;
	}
}
