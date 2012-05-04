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

	protected $ldapUserFilter;
	protected $ldapLoginFilter;
	protected $ldapQuotaAttribute;
	protected $ldapQuotaDefault;
	protected $ldapEmailAttribute;

	// will be retrieved from LDAP server
	protected $ldap_dc = false;

	function __construct() {
		$this->ldap_host = OCP\Config::getAppValue('user_ldap', 'ldap_host','');
		$this->ldap_port = OCP\Config::getAppValue('user_ldap', 'ldap_port', OC_USER_BACKEND_LDAP_DEFAULT_PORT	);
		$this->ldap_dn = OCP\Config::getAppValue('user_ldap', 'ldap_dn','');
		$this->ldap_password = OCP\Config::getAppValue('user_ldap', 'ldap_password','');
		$this->ldap_base = OCP\Config::getAppValue('user_ldap', 'ldap_base','');
		$this->ldap_login_filter = OCP\Config::getAppValue('user_ldap', 'ldap_login_filter','');
		$this->ldap_userlist_filter = OCP\Config::getAppValue('user_ldap', 'ldap_userlist_filter','objectClass=person');
		$this->ldap_tls = OCP\Config::getAppValue('user_ldap', 'ldap_tls', 0);
		$this->ldap_nocase = OCP\Config::getAppValue('user_ldap', 'ldap_nocase', 0);
		$this->ldap_display_name = OCP\Config::getAppValue('user_ldap', 'ldap_display_name', OC_USER_BACKEND_LDAP_DEFAULT_DISPLAY_NAME);
		$this->ldap_quota_attr = OCP\Config::getAppValue('user_ldap', 'ldap_quota_attr','');
		$this->ldap_quota_def = OCP\Config::getAppValue('user_ldap', 'ldap_quota_def','');
		$this->ldap_email_attr = OCP\Config::getAppValue('user_ldap', 'ldap_email_attr','');

		$this->ldapUserFilter      = OCP\Config::getAppValue('user_ldap', 'ldap_userlist_filter', '(objectClass=posixAccount)');
		$this->ldapLoginFilter     = OCP\Config::getAppValue('user_ldap', 'ldap_login_filter', '(uid=%uid)');
		$this->ldapQuotaAttribute  = OCP\Config::getAppValue('user_ldap', 'ldap_quota_attr', '');
		$this->ldapQuotaDefault    = OCP\Config::getAppValue('user_ldap', 'ldap_quota_def', '');
		$this->ldapEmailAttribute  = OCP\Config::getAppValue('user_ldap', 'ldap_email_attr', '');

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

	private function updateQuota($dn) {
		$quota = null;
		if(!empty($this->ldapQuotaDefault)) {
			$quota = $this->ldapQuotaDefault;
		}
		if(!empty($this->ldapQuotaAttribute)) {
			$aQuota = OC_LDAP::readAttribute($dn, $this->ldapQuotaAttribute);

			if($aQuota && (count($aQuota) > 0)) {
				$quota = $aQuota[0];
			}
		}
		if(!is_null($quota)) {
			OCP\Config::setUserValue(OC_LDAP::dn2username($dn), 'files', 'quota', OCP\Util::computerFileSize($quota));
		}
	}

	private function updateEmail($dn) {
		$email = null;
		if(!empty($this->ldapEmailAttribute)) {
			$aEmail = OC_LDAP::readAttribute($dn, $this->ldapEmailAttribute);
			if($aEmail && (count($aEmail) > 0)) {
				$email = $aEmail[0];
			}
			if(!is_null($email)){
				OCP\Config::setUserValue(OC_LDAP::dn2username($dn), 'settings', 'email', $email);
			}
		}
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

	/**
	 * @brief Check if the password is correct
	 * @param $uid The username
	 * @param $password The password
	 * @returns true/false
	 *
	 * Check if the password is correct without logging in the user
	 */
	public static function checkPassword( $uid, $password ){
		//find out dn of the user name
		$filter = str_replace('%uid', $uid, $this->ldapLoginFilter);
		$ldap_users = OC_LDAP::fetchListOfUsers($filter, 'dn');
		if(count($ldap_users) < 1) {
			return false;
		}
		$dn = $ldap_users[0];

		//are the credentials OK?
		if(!OC_LDAP::areCredentialsValid($dn, $password)) {
			return false;
		}

		//update some settings, if necessary
		$this->updateQuota($dn);
		$this->updateEmail($dn);

		//give back the display name
		return OC_LDAP::dn2username($dn);
	}

	/**
	 * @brief Get a list of all users
	 * @returns array with all uids
	 *
	 * Get a list of all users.
	 */
	public static function getUsers(){
		$ldap_users = OC_LDAP::fetchListOfUsers($this->ldapUserFilter, array(OC_LDAP::conf('ldapGroupDisplayName'), 'dn'));
		$users = OC_LDAP::ownCloudUserNames($ldap_users);
		return $users;
	}

	/**
	 * @brief check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 */
	public static function userExists($uid){
		return in_array($uid, self::getUsers());
	}


}

?>
