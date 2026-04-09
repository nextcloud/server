<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\ILDAPUserPlugin;

class LDAPUserPluginDummy implements ILDAPUserPlugin {
	public function respondToActions() {
		return null;
	}

	public function createUser($username, $password) {
		return null;
	}

	public function setPassword($uid, $password) {
		return null;
	}

	public function getHome($uid) {
		return null;
	}

	public function getDisplayName($uid) {
		return null;
	}

	public function setDisplayName($uid, $displayName) {
		return null;
	}

	public function canChangeAvatar($uid) {
		return null;
	}

	public function countUsers() {
		return null;
	}

	public function canDeleteUser() {
		return true;
	}

	public function deleteUser($uid) {
		return null;
	}
}
