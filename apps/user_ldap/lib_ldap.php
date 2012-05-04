<?php

/**
 * ownCloud – LDAP lib
 *
 * @author Arthur Schiwon
 * @copyright 2012 Arthur Schiwon blizzz@owncloud.com
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

define('LDAP_GROUP_MEMBER_ASSOC_ATTR','uniqueMember');
define('LDAP_GROUP_DISPLAY_NAME_ATTR','cn');

//needed to unbind, because we use OC_LDAP only statically
class OC_LDAP_DESTRUCTOR {
	public function __destruct() {
		OC_LDAP::destruct();
	}
}

class OC_LDAP {
	static protected $ldapConnectionRes = false;
	static protected $configured = false;

	//cached settings
	static protected $ldapHost;
	static protected $ldapPort;
	static protected $ldapBase;
	static protected $ldapBaseUsers;
	static protected $ldapBaseGroups;
	static protected $ldapAgentName;
	static protected $ldapAgentPassword;
	static protected $ldapTLS;
	static protected $ldapNoCase;
	// user and group settings, that are needed in both backends
	static protected $ldapUserDisplayName;
	static protected $ldapUserFilter;
	static protected $ldapGroupDisplayName;

	static public function init() {
		self::readConfiguration();
		self::establishConnection();
	}

	static public function destruct() {
		@ldap_unbind(self::$ldapConnectionRes);
	}

	/**
	 * @brief returns a read-only configuration value
	 * @param $key the name of the configuration value
	 * @returns the value on success, otherwise null
	 *
	 * returns a read-only configuration values
	 *
	 * we cannot work with getters, because it is a static class
	 */
	static public function conf($key) {
		if(!self::$configured) {
			self::init();
		}

		$availableProperties = array(
			'ldapUserDisplayName',
			'ldapGroupDisplayName',
		);

		if(in_array($key, $availableProperties)) {
			return self::$$key;
		}

		return null;
	}

	/**
	 * gives back the database table for the query
	 */
	static private function getMapTable($isUser) {
		if($isUser) {
			return '*PREFIX*ldap_user_mapping';
		} else {
			return '*PREFIX*ldap_group_mapping';
		}
	}

	/**
	 * @brief returns the LDAP DN for the given internal ownCloud name of the group
	 * @param $name the ownCloud name in question
	 * @returns string with the LDAP DN on success, otherwise false
	 *
	 * returns the LDAP DN for the given internal ownCloud name of the group
	 */
	static public function groupname2dn($name) {
		return self::ocname2dn($name, false);
	}

	/**
	 * @brief returns the LDAP DN for the given internal ownCloud name of the user
	 * @param $name the ownCloud name in question
	 * @returns string with the LDAP DN on success, otherwise false
	 *
	 * returns the LDAP DN for the given internal ownCloud name of the user
	 */
	static public function username2dn($name) {
		$dn = self::ocname2dn($name, true);
		if($dn) {
			return $dn;
		} else {
			//fallback: user is not mapped
			$filter = self::combineFilterWithAnd(array(
				self::$ldapUserFilter,
				self::$ldapUserDisplayName . '=' . $name,
			));
			$result = self::searchUsers($filter, 'dn');
			if(isset($result[0]['dn'])) {
				self::mapUser($result[0], $name);
				return $result[0];
			}
		}

		return false;
	}

	static private function ocname2dn($name, $isUser) {
		$table = self::getMapTable($isUser);

		$query = OCP\DB::prepare('
			SELECT ldap_dn
			FROM '.$table.'
			WHERE owncloud_name = ?
		');

		$record = $query->execute(array($name))->fetchOne();
		return $record;
		if($name=='Coyotes') {
			echo("adsfasdf ");
			var_dump($record);
			die();
		}
		if(isset($record['ldap_dn'])) {
			return $record['ldap_dn'];
		}

		return false;
	}

	/**
	 * @brief returns the internal ownCloud name for the given LDAP DN of the group
	 * @param $dn the dn of the group object
	 * @param $ldapname optional, the display name of the object
	 * @returns string with with the name to use in ownCloud
	 *
	 * returns the internal ownCloud name for the given LDAP DN of the group
	 */
	static public function dn2groupname($dn, $ldapname = null) {
		return self::dn2ocname($dn, $ldapname, false);
	}

	/**
	 * @brief returns the internal ownCloud name for the given LDAP DN of the user
	 * @param $dn the dn of the user object
	 * @param $ldapname optional, the display name of the object
	 * @returns string with with the name to use in ownCloud
	 *
	 * returns the internal ownCloud name for the given LDAP DN of the user
	 */
	static public function dn2username($dn, $ldapname = null) {
		return self::dn2ocname($dn, $ldapname, true);
	}

	static public function dn2ocname($dn, $ldapname = null, $isUser = true) {
		$table = self::getMapTable($isUser);
		if($isUser) {
			$nameAttribute = self::conf('ldapUserDisplayName');
		} else {
			$nameAttribute = self::conf('ldapGroupDisplayName');
		}

		$query = OCP\DB::prepare('
			SELECT owncloud_name
			FROM '.$table.'
			WHERE ldap_dn = ?
		');

		$component = $query->execute(array($dn))->fetchOne();
		if($component) {
			return $component;
		}

		if(is_null($ldapname)) {
			$ldapname = self::readAttribute($dn, $nameAttribute);
			$ldapname = $ldapname[0];
		}

		//a new user/group! Then let's try to add it. We're shooting into the blue with the user/group name, assuming that in most cases there will not be a conflict. Otherwise an error will occur and we will continue with our second shot.
		if(self::mapComponent($dn, $ldapname, $isUser)) {
			return $ldapname;
		}

		//doh! There is a conflict. We need to distinguish between users/groups. Adding indexes is an idea, but not much of a help for the user. The DN is ugly, but for now the only reasonable way. But we transform it to a readable format and remove the first part to only give the path where this object is located.
		$oc_name = self::alternateOwnCloudName($ldapname, $dn);
		if(self::mapComponent($dn, $oc_name, $isUser)) {
			return $oc_name;
		}

		//and this of course should never been thrown :)
		throw new Exception('LDAP backend: unexpected collision of DN and ownCloud Name.');
	}

	/**
	 * @brief gives back the user names as they are used ownClod internally
	 * @param $ldapGroups an array with the ldap Users result in style of array ( array ('dn' => foo, 'uid' => bar), ... )
	 * @returns an array with the user names to use in ownCloud
	 *
	 * gives back the user names as they are used ownClod internally
	 */
	static public function ownCloudUserNames($ldapUsers) {
		return self::ldap2ownCloudNames($ldapUsers, true);
	}

	/**
	 * @brief gives back the group names as they are used ownClod internally
	 * @param $ldapGroups an array with the ldap Groups result in style of array ( array ('dn' => foo, 'cn' => bar), ... )
	 * @returns an array with the group names to use in ownCloud
	 *
	 * gives back the group names as they are used ownClod internally
	 */
	static public function ownCloudGroupNames($ldapGroups) {
		return self::ldap2ownCloudNames($ldapGroups, false);
	}

	static private function ldap2ownCloudNames($ldapObjects, $isUsers) {
		if($isUsers) {
			$knownObjects = self::mappedUsers();
			$nameAttribute = self::conf('ldapUserDisplayName');
		} else {
			$knownObjects = self::mappedGroups();
			$nameAttribute = self::conf('ldapGroupDisplayName');
		}
		$ownCloudNames = array();

		foreach($ldapObjects as $ldapObject) {
			$key = self::recursiveArraySearch($knownObjects, $ldapObject['dn']);

			//everything is fine when we know the group
			if($key) {
				$ownCloudNames[] = $knownObjects[$key]['owncloud_name'];
				continue;
			}

			//a new group! Then let's try to add it. We're shooting into the blue with the group name, assuming that in most cases there will not be a conflict
			if(self::mapComponent($ldapObject['dn'], $ldapObject[$nameAttribute], $isUsers)) {
				$ownCloudNames[] = $ldapObject[$nameAttribute];
				continue;
			}

			//doh! There is a conflict. We need to distinguish between groups. Adding indexes is an idea, but not much of a help for the user. The DN is ugly, but for now the only reasonable way. But we transform it to a readable format and remove the first part to only give the path where this entry is located.
			$oc_name = self::alternateOwnCloudName($ldapObject[$nameAttribute], $ldapObject['dn']);
			if(self::mapComponent($ldapObject['dn'], $oc_name, $isUsers)) {
				$ownCloudNames[] = $oc_name;
				continue;
			}

			//and this of course should never been thrown :)
			throw new Exception('LDAP backend: unexpected collision of DN and ownCloud Name.');
		}
		return $ownCloudNames;
	}

	/**
	 * @brief creates a hopefully unique name for owncloud based on the display name and the dn of the LDAP object
	 * @param $name the display name of the object
	 * @param $dn the dn of the object
	 * @returns string with with the name to use in ownCloud
	 *
	 * creates a hopefully unique name for owncloud based on the display name and the dn of the LDAP object
	 */
	static private function alternateOwnCloudName($name, $dn) {
		$ufn = ldap_dn2ufn($dn);
		return $name . ' (' . trim(substr_replace($ufn, '', 0, strpos($ufn, ','))) . ')';
	}

	/**
	 * @brief retrieves all known groups from the mappings table
	 * @returns array with the results
	 *
	 * retrieves all known groups from the mappings table
	 */
	static private function mappedGroups() {
		return self::mappedComponents(false);
	}

	/**
	 * @brief retrieves all known users from the mappings table
	 * @returns array with the results
	 *
	 * retrieves all known users from the mappings table
	 */
	static private function mappedUsers() {
		return self::mappedComponents(true);
	}

	static private function mappedComponents($isUsers) {
		$table = self::getMapTable($isUsers);

		$query = OCP\DB::prepare('
			SELECT ldap_dn, owncloud_name
			FROM '. $table
		);

		return $query->execute()->fetchAll();
	}

	/**
	 * @brief inserts a new group into the mappings table
	 * @param $dn the record in question
	 * @param $ocname the name to use in ownCloud
	 * @returns true on success, false otherwise
	 *
	 * inserts a new group into the mappings table
	 */
	static private function mapGroup($dn, $ocname) {
		return self::mapComponent($dn, $ocname, false);
	}

	/**
	 * @brief inserts a new user into the mappings table
	 * @param $dn the record in question
	 * @param $ocname the name to use in ownCloud
	 * @returns true on success, false otherwise
	 *
	 * inserts a new user into the mappings table
	 */
	static private function mapUser($dn, $ocname) {
		return self::mapComponent($dn, $ocname, true);
	}

	/**
	 * @brief inserts a new user or group into the mappings table
	 * @param $dn the record in question
	 * @param $ocname the name to use in ownCloud
	 * @param $isUser is it a user or a group?
	 * @returns true on success, false otherwise
	 *
	 * inserts a new user or group into the mappings table
	 */
	static private function mapComponent($dn, $ocname, $isUser = true) {
		$table = self::getMapTable($isUser);

		$insert = OCP\DB::prepare('
			INSERT IGNORE INTO '.$table.'
			(ldap_dn, owncloud_name)
			VALUES (?,?)
		');

		$res = $insert->execute(array($dn, $ocname));

		return !OCP\DB::isError($res);
	}

	/**
	 * @brief reads a given attribute for an LDAP record identified by a DN
	 * @param $dn the record in question
	 * @param $attr the attribute that shall be retrieved
	 * @returns the values in an array on success, false otherwise
	 *
	 * Reads an attribute from an LDAP entry
	 */
	static public function readAttribute($dn, $attr) {
		$cr = self::getConnectionResource();
// echo("<pre>");var_dump($dn);
		$rr = ldap_read($cr, $dn, 'objectClass=*', array($attr));
		if(!$rr) {
			echo('<pre>###RA ');var_dump($dn);var_dump(debug_backtrace());die();
		}
		$er = ldap_first_entry($cr, $rr);
		$result = ldap_get_attributes($cr, $er);

// 		if($dn == 'cn=Coyotes,cn=groups,dc=blizzz-oc,dc=bzoc') die((var_dump($result)));
		if($result[$attr]['count'] > 0){
			$values = array();
			for($i=0;$i<$result[$attr]['count'];$i++) {
				$values[] = $result[$attr][$i];
			}
			return $values;
		}
		return false;
	}

	/**
	 * @brief executes an LDAP search, optimized for Users
	 * @param $filter the LDAP filter for the search
	 * @param $attr optional, when a certain attribute shall be filtered out
	 * @returns array with the search result
	 *
	 * Executes an LDAP search
	 */
	static public function searchUsers($filter, $attr = null) {
		return self::search($filter, self::$ldapBaseUsers, $attr);
	}

	/**
	 * @brief executes an LDAP search, optimized for Groups
	 * @param $filter the LDAP filter for the search
	 * @param $attr optional, when a certain attribute shall be filtered out
	 * @returns array with the search result
	 *
	 * Executes an LDAP search
	 */
	static public function searchGroups($filter, $attr = null) {
		return self::search($filter, self::$ldapBaseGroups, $attr);
	}

	/**
	 * @brief executes an LDAP search
	 * @param $filter the LDAP filter for the search
	 * @param $base the LDAP subtree that shall be searched
	 * @param $attr optional, when a certain attribute shall be filtered out
	 * @returns array with the search result
	 *
	 * Executes an LDAP search
	 */
	static private function search($filter, $base, $attr = null) {
		if(!is_null($attr) && !is_array($attr)) {
			$attr = array(strtolower($attr));
		}

		$sr = @ldap_search(self::getConnectionResource(), $base, $filter, $attr);
		$findings = @ldap_get_entries(self::getConnectionResource(), $sr );
		// if we're here, probably no connection ressource is returned.
		// to make ownCloud behave nicely, we simply give back an empty array.
		if(is_null($findings)) {
			return array();
		}

		if(!is_null($attr)) {
			$selection = array();
			$multiarray = false;
			if(count($attr) > 1) {
				$multiarray = true;
				$i = 0;
			}
			foreach($findings as $item) {
				if($multiarray) {
					foreach($attr as $key) {
						if(isset($item[$key])) {
							if($key != 'dn'){
								$selection[$i][$key] = $item[$key][0];
							} else {
								$selection[$i][$key] = $item[$key];
							}
						}

					}
					$i++;
				} else {
					if(isset($item[$attr[0]])) {
						$selection[] = $item[$attr[0]];
					}
				}

			}
			return $selection;
		}

		return $findings;
	}

	/**
	 * @brief combines the input filters with AND
	 * @param $filters array, the filters to connect
	 * @returns the combined filter
	 *
	 * Combines Filter arguments with AND
	 */
	static public function combineFilterWithAnd($filters) {
		return self::combineFilter($filters,'&');
	}

	/**
	 * @brief combines the input filters with AND
	 * @param $filters array, the filters to connect
	 * @returns the combined filter
	 *
	 * Combines Filter arguments with AND
	 */
	static public function combineFilterWithOr($filters) {
		return self::combineFilter($filters,'|');
	}

	/**
	 * @brief combines the input filters with given operator
	 * @param $filters array, the filters to connect
	 * @param $operator either & or |
	 * @returns the combined filter
	 *
	 * Combines Filter arguments with AND
	 */
	static private function combineFilter($filters, $operator) {
		$combinedFilter = '('.$operator;
		foreach($filters as $filter) {
		    if(substr($filter,0,1) != '(') {
				$filter = '('.$filter.')';
		    }
		    $combinedFilter.=$filter;
		}
		$combinedFilter.=')';
		return $combinedFilter;
	}

	/**
	 * Returns the LDAP handler
	 */
	static private function getConnectionResource() {
		if(!self::$ldapConnectionRes) {
			self::init();
		}
		if(is_null(self::$ldapConnectionRes)) {
			OCP\Util::writeLog('ldap', 'Connection could not be established', OCP\Util::INFO);
		}
		return self::$ldapConnectionRes;
	}

	/**
	 * Caches the general LDAP configuration.
	 */
	static private function readConfiguration() {
		if(!self::$configured) {
			self::$ldapHost             = OCP\Config::getAppValue('user_ldap', 'ldap_host', '');
			self::$ldapPort             = OCP\Config::getAppValue('user_ldap', 'ldap_port', OC_USER_BACKEND_LDAP_DEFAULT_PORT);
			self::$ldapAgentName        = OCP\Config::getAppValue('user_ldap', 'ldap_dn','');
			self::$ldapAgentPassword    = OCP\Config::getAppValue('user_ldap', 'ldap_password','');
			self::$ldapBase             = OCP\Config::getAppValue('user_ldap', 'ldap_base', '');
			self::$ldapBaseUsers        = OCP\Config::getAppValue('user_ldap', 'ldap_base_users',self::$ldapBase);
			self::$ldapBaseGroups       = OCP\Config::getAppValue('user_ldap', 'ldap_base_groups', self::$ldapBase);
			self::$ldapTLS              = OCP\Config::getAppValue('user_ldap', 'ldap_tls',0);
			self::$ldapNoCase           = OCP\Config::getAppValue('user_ldap', 'ldap_nocase', 0);
			self::$ldapUserDisplayName  = OCP\Config::getAppValue('user_ldap', 'ldap_display_name', OC_USER_BACKEND_LDAP_DEFAULT_DISPLAY_NAME);
			self::$ldapUserFilter       = OCP\Config::getAppValue('user_ldap', 'ldap_userlist_filter','objectClass=person');
			self::$ldapGroupDisplayName = OCP\Config::getAppValue('user_ldap', 'ldap_group_display_name', LDAP_GROUP_DISPLAY_NAME_ATTR);

			if(
				   !empty(self::$ldapHost)
				&& !empty(self::$ldapPort)
				&& (
					   (!empty(self::$ldapAgentName) && !empty(self::$ldapAgentPassword))
					|| ( empty(self::$ldapAgentName) &&  empty(self::$ldapAgentPassword))
				)
				&& !empty(self::$ldapBase)
				&& !empty(self::$ldapBaseUsers)
				&& !empty(self::$ldapBaseGroups)
				&& !empty(self::$ldapUserDisplayName)
			)
			{
				self::$configured = true;
			}
		}
	}

	/**
	 * Connects and Binds to LDAP
	 */
	static private function establishConnection() {
		if(!self::$configured) {
			return false;
		}
		if(!self::$ldapConnectionRes) {
			self::$ldapConnectionRes = ldap_connect(self::$ldapHost, self::$ldapPort);
			if(ldap_set_option(self::$ldapConnectionRes, LDAP_OPT_PROTOCOL_VERSION, 3)) {
					if(ldap_set_option(self::$ldapConnectionRes, LDAP_OPT_REFERRALS, 0)) {
						if(self::$ldapTLS) {
							ldap_start_tls(self::$ldapConnectionRes);
						}
					}
			}

			$ldapLogin = @ldap_bind(self::$ldapConnectionRes, self::$ldapAgentName, self::$ldapAgentPassword );
			if(!$ldapLogin) {
				return false;
			}
		}
	}

	/**
	* taken from http://www.php.net/manual/en/function.array-search.php#97645
	* TODO: move somewhere, where its better placed since it is not LDAP specific. OC_Helper maybe?
	*/
	static public function recursiveArraySearch($haystack, $needle, $index = null) {
		$aIt = new RecursiveArrayIterator($haystack);
		$it = new RecursiveIteratorIterator($aIt);

		while($it->valid()) {
			if (((isset($index) AND ($it->key() == $index)) OR (!isset($index))) AND ($it->current() == $needle)) {
				return $aIt->key();
			}

			$it->next();
		}

		return false;
	}

 }