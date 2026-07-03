<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\ILDAPGroupPlugin;

class LDAPGroupPluginDummy implements ILDAPGroupPlugin {
	public function respondToActions(): int {
		return 0;
	}

	public function createGroup(string $gid): ?string {
		return null;
	}

	public function deleteGroup(string $gid): false {
		return false;
	}

	public function addToGroup(string $uid, string $gid): false {
		return false;
	}

	public function removeFromGroup(string $uid, string $gid): false {
		return false;
	}
}
