<?php
/**
* ownCloud
*
* @author Frank Karlitschek
* @author Tom Needham
* @copyright 2012 Frank Karlitschek frank@owncloud.org
* @copyright 2012 Tom Needham tom@owncloud.com
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

class OC_OCS_Person {

	public static function check($parameters) {
		$login = isset($_POST['login']) ? $_POST['login'] : false;
		$password = isset($_POST['password']) ? $_POST['password'] : false;
		if($login && $password) {
			if(OC_User::checkPassword($login, $password)) {
				$xml['person']['personid'] = $login;
				return new OC_OCS_Result($xml);
			} else {
				return new OC_OCS_Result(null, 102);
			}
		} else {
			return new OC_OCS_Result(null, 101);
		}
	}

}
