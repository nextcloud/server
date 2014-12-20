<?php

/**
 * ownCloud – LDAP User
 *
 * @author Arthur Schiwon
 * @copyright 2014 Arthur Schiwon blizzz@owncloud.com
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

namespace OCA\user_ldap\lib\user;

/**
 * IUserTools
 *
 * defines methods that are required by User class for LDAP interaction
 */
interface IUserTools {
	public function getConnection();

	public function readAttribute($dn, $attr, $filter = 'objectClass=*');

	public function stringResemblesDN($string);

	public function dn2username($dn, $ldapname = null);

	public function username2dn($name);
}
