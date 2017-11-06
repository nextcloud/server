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

}
