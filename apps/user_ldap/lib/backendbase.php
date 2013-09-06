<?php

/**
 * ownCloud – LDAP BackendBase
 *
 * @author Arthur Schiwon
 * @copyright 2013 Arthur Schiwon blizzz@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\user_ldap\lib;

abstract class BackendBase {
	protected $ldap;

	public function __construct() {
		$this->ldap = new LDAP();
	}

	/**
	 * @brief sets the LDAP Wrapper to be used
	 *
	 * @param $ldapWrapper an instance of the Wrapper
	 * @return true on success, otherwise false
	 *
	 * The LDAP Wrapper must implement the PHP LDAP functions, which are used
	 * in the LDAP backend
	 */
	public function setLDAPWrapper(ILDAPWrapper $ldapWrapper) {
		$this->ldap = $ldapWrapper;
		return true;
	}
}