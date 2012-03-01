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
	protected $ldap_login_filter;
	protected $ldap_userlist_filter;
	protected $ldap_tls;
	protected $ldap_nocase;
	protected $ldap_display_name;
	protected $ldap_quota;
	protected $ldap_quota_def;
	protected $ldap_email;

	// will be retrieved from LDAP server
	protected $ldap_dc = false;

	function __construct() {
		$this->ldap_host = OC_Appconfig::getValue('user_ldap', 'ldap_host','');
		$this->ldap_port = OC_Appconfig::getValue('user_ldap', 'ldap_port', OC_USER_BACKEND_LDAP_DEFAULT_PORT	);
		$this->ldap_dn = OC_Appconfig::getValue('user_ldap', 'ldap_dn','');
		$this->ldap_password = OC_Appconfig::getValue('user_ldap', 'ldap_password','');
		$this->ldap_base = OC_Appconfig::getValue('user_ldap', 'ldap_base','');
		$this->ldap_login_filter = OC_Appconfig::getValue('user_ldap', 'ldap_login_filter','');
		$this->ldap_userlist_filter = OC_Appconfig::getValue('user_ldap', 'ldap_userlist_filter','objectClass=person');
		$this->ldap_tls = OC_Appconfig::getValue('user_ldap', 'ldap_tls', 0);
		$this->ldap_nocase = OC_Appconfig::getValue('user_ldap', 'ldap_nocase', 0);
		$this->ldap_display_name = OC_Appconfig::getValue('user_ldap', 'ldap_display_name', OC_USER_BACKEND_LDAP_DEFAULT_DISPLAY_NAME);
		$this->ldap_quota_attr = OC_Appconfig::getValue('user_ldap', 'ldap_quota_attr','');
		$this->ldap_quota_def = OC_Appconfig::getValue('user_ldap', 'ldap_quota_def','');
		$this->ldap_email_attr = OC_Appconfig::getValue('user_ldap', 'ldap_email_attr','');

		if( !empty($this->ldap_host)
			&& !empty($this->ldap_port)
			&& ((!empty($this->ldap_dn) && !empty($this->ldap_password)) || (empty($this->ldap_dn) && empty($this->ldap_password)))
			&& !empty($this->ldap_base)
			&& !empty($this->ldap_login_filter)
			&& !empty($this->ldap_display_name)
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

	private function setQuota( $uid ) {
		if( !$this->ldap_dc )
			return false;

		if(!empty($this->ldap_quota_attr)) {
			$quota = $this->ldap_dc[strtolower($this->ldap_quota_attr)][0];
		} else {
			$quota = false;
		}
		$quota = $quota != -1 ? $quota : $this->ldap_quota_def;
		OC_Preferences::setValue($uid, 'files', 'quota', OC_Helper::computerFileSize($quota));
	}

	private function setEmail( $uid ) {
		if( !$this->ldap_dc )
			return false;

		$email = OC_Preferences::getValue($uid, 'settings', 'email', '');
		if ( !empty( $email ) )
			return false;

		$email = $this->ldap_dc[$this->ldap_email_attr][0];
		OC_Preferences::setValue($uid, 'settings', 'email', $email);
	}

	//Connect to LDAP and store the resource
	private function getDs() {
		if(!$this->ds) {
			$this->ds = ldap_connect( $this->ldap_host, $this->ldap_port );
				if(ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, 3))
					if(ldap_set_option($this->ds, LDAP_OPT_REFERRALS, 0))
						if($this->ldap_tls)
							ldap_start_tls($this->ds);
		}
		//TODO: Not necessary to perform a bind each time, is it?
		// login
		if(!empty($this->ldap_dn)) {
			$ldap_login = @ldap_bind( $this->ds, $this->ldap_dn, $this->ldap_password );
			if(!$ldap_login) {
				return false;
			}
		}

		return $this->ds;
	}

	private function getDc( $uid ) {
		if(!$this->configured)
			return false;

		// connect to server
		$ds = $this->getDs();
		if( !$ds )
			return false;

		// get dn
		$filter = str_replace('%uid', $uid, $this->ldap_login_filter);
		$sr = ldap_search( $this->getDs(), $this->ldap_base, $filter );
		$entries = ldap_get_entries( $this->getDs(), $sr );

		if( $entries['count'] == 0 ) {
			return false;
		}

		$this->ldap_dc = $entries[0];

		return $this->ldap_dc;
	}

	public function checkPassword( $uid, $password ) {
		if(!$this->configured){
			return false;
		}
		$dc = $this->getDc( $uid );
		if( !$dc )
			return false;

		if (!@ldap_bind( $this->getDs(), $dc['dn'], $password )) {
			return false;
		}

		if(!empty($this->ldap_quota_attr) || !empty($this->ldap_quota_def)) {
			$this->setQuota($uid);
		}

		if(!empty($this->ldap_email_attr)) {
			$this->setEmail($uid);
		}

		if($this->ldap_nocase) {
			$filter = str_replace('%uid', $uid, $this->ldap_login_filter);
			$sr = ldap_search( $this->getDs(), $this->ldap_base, $filter );
			$entries = ldap_get_entries( $this->getDs(), $sr );
			if( $entries['count'] == 1 ) {
				foreach($entries as $row) {
					$ldap_display_name  = strtolower($this->ldap_display_name);
					if(isset($row[$ldap_display_name])) {
						return $row[$ldap_display_name][0];
					}
				}
			}
			else {
				return $uid;
			}

		}
		else {
			return $uid;
		}

	}

	public function userExists( $uid ) {
		if(!$this->configured){
			return false;
		}
		$dn = $this->getDc($uid);
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
		$sr = ldap_search( $this->getDs(), $this->ldap_base, $this->ldap_userlist_filter );
		$entries = ldap_get_entries( $this->getDs(), $sr );
		if( $entries['count'] == 0 )
			return false;
		else {
			$users = array();
			foreach($entries as $row) {
				// TODO ldap_get_entries() seems to lower all keys => needs review
				$ldap_display_name  = strtolower($this->ldap_display_name);
				if(isset($row[$ldap_display_name])) {
					$users[] = $row[$ldap_display_name][0];
				}
			}
			// TODO language specific sorting of user names
			sort($users);
			return $users;
		}
	}

}

?>
