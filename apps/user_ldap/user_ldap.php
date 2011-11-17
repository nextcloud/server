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

class OC_USER_LDAP extends OC_User_Backend {

	protected $ds;
	protected $configured = false;

	// cached settings
	protected $ldap_host;
	protected $ldap_port;
	protected $ldap_dn;
	protected $ldap_password;
	protected $ldap_base;
	protected $ldap_filter;
	protected $ldap_tls;

	function __construct() {
		$this->ldap_host = OC_Appconfig::getValue('user_ldap', 'ldap_host','');
		$this->ldap_port = OC_Appconfig::getValue('user_ldap', 'ldap_port', OC_USER_BACKEND_LDAP_DEFAULT_PORT	);
		$this->ldap_dn = OC_Appconfig::getValue('user_ldap', 'ldap_dn','');
		$this->ldap_password = OC_Appconfig::getValue('user_ldap', 'ldap_password','');
		$this->ldap_base = OC_Appconfig::getValue('user_ldap', 'ldap_base','');
		$this->ldap_filter = OC_Appconfig::getValue('user_ldap', 'ldap_filter','');
		$this->ldap_tls = OC_Appconfig::getValue('user_tls', 'ldap_tls', 0);

		if( !empty($this->ldap_host)
			&& !empty($this->ldap_port)
			&& ((!empty($this->ldap_dn) && !empty($this->ldap_password)) || (empty($this->ldap_dn) && empty($this->ldap_password)))
			&& !empty($this->ldap_base)
			&& !empty($this->ldap_filter)
		)
		{
			$this->configured = true;
		}
	}

	function __destruct() {
		// close the connection
		if( $this->ds )
			ldap_unbind($this->ds);
	}

	private function getDs() {
		if(!$this->ds) {
			$this->ds = ldap_connect( $this->ldap_host, $this->ldap_port );
				if(ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, 3))
					if(ldap_set_option($this->ds, LDAP_OPT_REFERRALS, 0))
						if($this->ldap_tls)
							ldap_start_tls($this->ds);
		}

		// login
		if(!empty($this->ldap_dn)) {
			$ldap_login = @ldap_bind( $this->ds, $this->ldap_dn, $this->ldap_password );
			if(!$ldap_login)
				return false;
		}

		return $this->ds;
	}

	private function getDn( $uid ) {
		if(!$this->configured)
			return false;

		// connect to server
		$ds = $this->getDs();
		if( !$ds )
			return false;

		// get dn
		$filter = str_replace("%uid", $uid, $this->ldap_filter);
		$sr = ldap_search( $this->getDs(), $this->ldap_base, $filter );
		$entries = ldap_get_entries( $this->getDs(), $sr );

		if( $entries["count"] == 0 )
			return false;

		return $entries[0]["dn"];
	}
	public function checkPassword( $uid, $password ) {
		if(!$this->configured){
			return false;
		}
		$dn = $this->getDn( $uid );
		if( !$dn )
			return false;

		if (!@ldap_bind( $this->getDs(), $dn, $password ))
			return false;
		return $uid;
	}

	public function userExists( $uid ) {
		if(!$this->configured){
			return false;
		}
		$dn = $this->getDn($uid);
		return !empty($dn);
	}
	
	public function getUsers()
	{
		if(!$this->configured)
		return false;
	
		// connect to server
		$ds = $this->getDs();
		if( !$ds )
			return false;
	
		// get users
		$filter = "objectClass=person";
		$sr = ldap_search( $this->getDs(), $this->ldap_base, $filter );
		$entries = ldap_get_entries( $this->getDs(), $sr );
	
		if( $entries["count"] == 0 )
			return false;
		else {
			$users = array();
			foreach($entries as $row) {
				if(isset($row['uid'])) {
					$users[] = $row['uid'][0];
				}
			}
		}
	
		return $users;
	}

}

?>
