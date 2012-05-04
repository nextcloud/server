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

	// cached settings
	protected $ldapUserFilter;
	protected $ldapLoginFilter;
	protected $ldapQuotaAttribute;
	protected $ldapQuotaDefault;
	protected $ldapEmailAttribute;

	// will be retrieved from LDAP server
	protected $ldap_dc = false;

	public function __construct() {
		$this->ldapUserFilter      = OCP\Config::getAppValue('user_ldap', 'ldap_userlist_filter', '(objectClass=posixAccount)');
		$this->ldapLoginFilter     = OCP\Config::getAppValue('user_ldap', 'ldap_login_filter', '(uid=%uid)');
		$this->ldapQuotaAttribute  = OCP\Config::getAppValue('user_ldap', 'ldap_quota_attr', '');
		$this->ldapQuotaDefault    = OCP\Config::getAppValue('user_ldap', 'ldap_quota_def', '');
		$this->ldapEmailAttribute  = OCP\Config::getAppValue('user_ldap', 'ldap_email_attr', '');
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

	/**
	 * @brief Check if the password is correct
	 * @param $uid The username
	 * @param $password The password
	 * @returns true/false
	 *
	 * Check if the password is correct without logging in the user
	 */
	public function checkPassword($uid, $password){
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
