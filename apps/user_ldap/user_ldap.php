<?php

/**
 * ownCloud
 *
 * @author Dominik Schmidt
 * @copyright 2011 Dominik Schmidt dev@dominik-schmidt.de
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

require_once('User/backend.php');

class OC_USER_LDAP extends OC_USER_BACKEND {

	public function checkPassword( $uid, $password ) {
		$ldap_host = OC_APPCONFIG::getValue('user_ldap', 'ldap_host','');
		$ldap_dn = OC_APPCONFIG::getValue('user_ldap', 'ldap_dn','');
		$ldap_password = OC_APPCONFIG::getValue('user_ldap', 'ldap_password','');
		$ldap_base = OC_APPCONFIG::getValue('user_ldap', 'ldap_base','');
		$ldap_filter = OC_APPCONFIG::getValue('user_ldap', 'ldap_filter','');

		// connect to server
		$ds = ldap_connect( $ldap_host );
		if( !$ds )
			return false;

		// login for search
		if(!empty($ldap_dn)) {
			$ldap_login = @ldap_bind( $ds, $ldap_dn, $ldap_password );
			if(!$ldap_login)
				return false;
		}

		// get dn
		$filter = str_replace("%uid", $uid, $ldap_filter);
		$sr = ldap_search( $ds, $ldap_base, $filter );
		$entries = ldap_get_entries( $ds, $sr );

		if( $entries["count"] == 0 )
			return false;

		$dn = $entries[0]["dn"];
		$result = @ldap_bind( $ds, $dn, $password );

		return $result;
	}
}

?>
