<?php
/**
 * @copyright Copyright (c) 2017 EITA Cooperative (eita.org.br)
 *
 * @author Vinicius Brand <vinicius@eita.org.br>
 * @author Daniel Tygel <dtygel@eita.org.br>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
