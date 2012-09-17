<?php

/**
 * ownCloud – LDAP Access
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

namespace OCA\user_ldap\lib;

abstract class Access {
	protected $connection;

	public function setConnector(Connection &$connection) {
		$this->connection = $connection;
	}

	private function checkConnection() {
		return ($this->connection instanceof Connection);
	}

	/**
	 * @brief reads a given attribute for an LDAP record identified by a DN
	 * @param $dn the record in question
	 * @param $attr the attribute that shall be retrieved
	 * @returns the values in an array on success, false otherwise
	 *
	 * Reads an attribute from an LDAP entry
	 */
	public function readAttribute($dn, $attr) {
		if(!$this->checkConnection()) {
			\OCP\Util::writeLog('user_ldap', 'No LDAP Connector assigned, access impossible for readAttribute.', \OCP\Util::WARN);
			return false;
		}
		$cr = $this->connection->getConnectionResource();
		if(!is_resource($cr)) {
			//LDAP not available
			\OCP\Util::writeLog('user_ldap', 'LDAP resource not available.', \OCP\Util::DEBUG);
			return false;
		}
		$rr = @ldap_read($cr, $dn, 'objectClass=*', array($attr));
		if(!is_resource($rr)) {
			\OCP\Util::writeLog('user_ldap', 'readAttribute '.$attr.' failed for DN '.$dn, \OCP\Util::DEBUG);
			//in case an error occurs , e.g. object does not exist
			return false;
		}
		$er = ldap_first_entry($cr, $rr);
		//LDAP attributes are not case sensitive
		$result = \OCP\Util::mb_array_change_key_case(ldap_get_attributes($cr, $er), MB_CASE_LOWER, 'UTF-8');
		$attr = mb_strtolower($attr, 'UTF-8');

		if(isset($result[$attr]) && $result[$attr]['count'] > 0) {
			$values = array();
			for($i=0;$i<$result[$attr]['count'];$i++) {
				$values[] = $this->resemblesDN($attr) ? $this->sanitizeDN($result[$attr][$i]) : $result[$attr][$i];
			}
			return $values;
		}
		\OCP\Util::writeLog('user_ldap', 'Requested attribute '.$attr.' not found for '.$dn, \OCP\Util::DEBUG);
		return false;
	}

	/**
	 * @brief checks wether the given attribute`s valua is probably a DN
	 * @param $attr the attribute in question
	 * @return if so true, otherwise false
	 */
	private function resemblesDN($attr) {
		$resemblingAttributes = array(
			'dn',
			'uniquemember',
			'member'
		);
		return in_array($attr, $resemblingAttributes);
	}

	/**
	 * @brief sanitizes a DN received from the LDAP server
	 * @param $dn the DN in question
	 * @return the sanitized DN
	 */
	private function sanitizeDN($dn) {
		//OID sometimes gives back DNs with whitespace after the comma a la "uid=foo, cn=bar, dn=..." We need to tackle this!
		$dn = preg_replace('/([^\\\]),(\s+)/u', '\1,', $dn);

		//make comparisons and everything work
		$dn = mb_strtolower($dn, 'UTF-8');

		return $dn;
	}

	/**
	 * gives back the database table for the query
	 */
	private function getMapTable($isUser) {
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
	public function groupname2dn($name) {
		return $this->ocname2dn($name, false);
	}

	/**
	 * @brief returns the LDAP DN for the given internal ownCloud name of the user
	 * @param $name the ownCloud name in question
	 * @returns string with the LDAP DN on success, otherwise false
	 *
	 * returns the LDAP DN for the given internal ownCloud name of the user
	 */
	public function username2dn($name) {
		$dn = $this->ocname2dn($name, true);
		if($dn) {
			return $dn;
		}

		return false;
	}

	/**
	 * @brief returns the LDAP DN for the given internal ownCloud name
	 * @param $name the ownCloud name in question
	 * @param $isUser is it a user? otherwise group
	 * @returns string with the LDAP DN on success, otherwise false
	 *
	 * returns the LDAP DN for the given internal ownCloud name
	 */
	private function ocname2dn($name, $isUser) {
		$table = $this->getMapTable($isUser);

		$query = \OCP\DB::prepare('
			SELECT `ldap_dn`
			FROM `'.$table.'`
			WHERE `owncloud_name` = ?
		');

		$record = $query->execute(array($name))->fetchOne();
		return $record;
	}

	/**
	 * @brief returns the internal ownCloud name for the given LDAP DN of the group
	 * @param $dn the dn of the group object
	 * @param $ldapname optional, the display name of the object
	 * @returns string with with the name to use in ownCloud, false on DN outside of search DN
	 *
	 * returns the internal ownCloud name for the given LDAP DN of the group, false on DN outside of search DN or failure
	 */
	public function dn2groupname($dn, $ldapname = null) {
		if(mb_strripos($dn, $this->sanitizeDN($this->connection->ldapBaseGroups), 0, 'UTF-8') !== (mb_strlen($dn, 'UTF-8')-mb_strlen($this->sanitizeDN($this->connection->ldapBaseGroups), 'UTF-8'))) {
			return false;
		}
		return $this->dn2ocname($dn, $ldapname, false);
	}

	/**
	 * @brief returns the internal ownCloud name for the given LDAP DN of the user
	 * @param $dn the dn of the user object
	 * @param $ldapname optional, the display name of the object
	 * @returns string with with the name to use in ownCloud
	 *
	 * returns the internal ownCloud name for the given LDAP DN of the user, false on DN outside of search DN or failure
	 */
	public function dn2username($dn, $ldapname = null) {
		if(mb_strripos($dn, $this->sanitizeDN($this->connection->ldapBaseUsers), 0, 'UTF-8') !== (mb_strlen($dn, 'UTF-8')-mb_strlen($this->sanitizeDN($this->connection->ldapBaseUsers), 'UTF-8'))) {
			return false;
		}
		return $this->dn2ocname($dn, $ldapname, true);
	}

	/**
	 * @brief returns an internal ownCloud name for the given LDAP DN
	 * @param $dn the dn of the user object
	 * @param $ldapname optional, the display name of the object
	 * @param $isUser optional, wether it is a user object (otherwise group assumed)
	 * @returns string with with the name to use in ownCloud
	 *
	 * returns the internal ownCloud name for the given LDAP DN of the user, false on DN outside of search DN
	 */
	public function dn2ocname($dn, $ldapname = null, $isUser = true) {
		$dn = $this->sanitizeDN($dn);
		$table = $this->getMapTable($isUser);
		if($isUser) {
			$nameAttribute = $this->connection->ldapUserDisplayName;
		} else {
			$nameAttribute = $this->connection->ldapGroupDisplayName;
		}

		$query = \OCP\DB::prepare('
			SELECT `owncloud_name`
			FROM `'.$table.'`
			WHERE `ldap_dn` = ?
		');

		//let's try to retrieve the ownCloud name from the mappings table
		$component = $query->execute(array($dn))->fetchOne();
		if($component) {
			return $component;
		}

		//second try: get the UUID and check if it is known. Then, update the DN and return the name.
		$uuid = $this->getUUID($dn);
		if($uuid) {
			$query = \OCP\DB::prepare('
				SELECT `owncloud_name`
				FROM `'.$table.'`
				WHERE `directory_uuid` = ?
			');
			$component = $query->execute(array($uuid))->fetchOne();
			if($component) {
				$query = \OCP\DB::prepare('
					UPDATE `'.$table.'`
					SET `ldap_dn` = ?
					WHERE `directory_uuid` = ?
				');
				$query->execute(array($dn, $uuid));
				return $component;
			}
		}

		if(is_null($ldapname)) {
			$ldapname = $this->readAttribute($dn, $nameAttribute);
			if(!isset($ldapname[0]) && empty($ldapname[0])) {
				\OCP\Util::writeLog('user_ldap', 'No or empty name for '.$dn.'.', \OCP\Util::INFO);
				return false;
			}
			$ldapname = $ldapname[0];
		}
		$ldapname = $this->sanitizeUsername($ldapname);

		//a new user/group! Then let's try to add it. We're shooting into the blue with the user/group name, assuming that in most cases there will not be a conflict. Otherwise an error will occur and we will continue with our second shot.
		if(($isUser && !\OCP\User::userExists($ldapname)) || (!$isUser && !\OC_Group::groupExists($ldapname))) {
			if($this->mapComponent($dn, $ldapname, $isUser)) {
				return $ldapname;
			}
		}

		//doh! There is a conflict. We need to distinguish between users/groups. Adding indexes is an idea, but not much of a help for the user. The DN is ugly, but for now the only reasonable way. But we transform it to a readable format and remove the first part to only give the path where this object is located.
		$oc_name = $this->alternateOwnCloudName($ldapname, $dn);
		if(($isUser && !\OCP\User::userExists($oc_name)) || (!$isUser && !\OC_Group::groupExists($oc_name))) {
			if($this->mapComponent($dn, $oc_name, $isUser)) {
				return $oc_name;
			}
		}

		//if everything else did not help..
		\OCP\Util::writeLog('user_ldap', 'Could not create unique ownCloud name for '.$dn.'.', \OCP\Util::INFO);
		return false;
	}

	/**
	 * @brief gives back the user names as they are used ownClod internally
	 * @param $ldapGroups an array with the ldap Users result in style of array ( array ('dn' => foo, 'uid' => bar), ... )
	 * @returns an array with the user names to use in ownCloud
	 *
	 * gives back the user names as they are used ownClod internally
	 */
	public function ownCloudUserNames($ldapUsers) {
		return $this->ldap2ownCloudNames($ldapUsers, true);
	}

	/**
	 * @brief gives back the group names as they are used ownClod internally
	 * @param $ldapGroups an array with the ldap Groups result in style of array ( array ('dn' => foo, 'cn' => bar), ... )
	 * @returns an array with the group names to use in ownCloud
	 *
	 * gives back the group names as they are used ownClod internally
	 */
	public function ownCloudGroupNames($ldapGroups) {
		return $this->ldap2ownCloudNames($ldapGroups, false);
	}

	private function ldap2ownCloudNames($ldapObjects, $isUsers) {
		if($isUsers) {
			$knownObjects = $this->mappedUsers();
			$nameAttribute = $this->connection->ldapUserDisplayName;
		} else {
			$knownObjects = $this->mappedGroups();
			$nameAttribute = $this->connection->ldapGroupDisplayName;
		}
		$ownCloudNames = array();

		foreach($ldapObjects as $ldapObject) {
			$key = \OCP\Util::recursiveArraySearch($knownObjects, $ldapObject['dn']);

			//everything is fine when we know the group
			if($key !== false) {
				$ownCloudNames[] = $knownObjects[$key]['owncloud_name'];
				continue;
			}

			$ocname = $this->dn2ocname($ldapObject['dn'], $ldapObject[$nameAttribute], $isUsers);
			if($ocname) {
				$ownCloudNames[] = $ocname;
			}
			continue;
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
	private function alternateOwnCloudName($name, $dn) {
		$ufn = ldap_dn2ufn($dn);
		$name = $name . '@' . trim(\OCP\Util::mb_substr_replace($ufn, '', 0, mb_strpos($ufn, ',', 0, 'UTF-8'), 'UTF-8'));
		$name = $this->sanitizeUsername($name);
		return $name;
	}

	/**
	 * @brief retrieves all known groups from the mappings table
	 * @returns array with the results
	 *
	 * retrieves all known groups from the mappings table
	 */
	private function mappedGroups() {
		return $this->mappedComponents(false);
	}

	/**
	 * @brief retrieves all known users from the mappings table
	 * @returns array with the results
	 *
	 * retrieves all known users from the mappings table
	 */
	private function mappedUsers() {
		return $this->mappedComponents(true);
	}

	private function mappedComponents($isUsers) {
		$table = $this->getMapTable($isUsers);

		$query = \OCP\DB::prepare('
			SELECT `ldap_dn`, `owncloud_name`
			FROM `'. $table . '`'
		);

		return $query->execute()->fetchAll();
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
	private function mapComponent($dn, $ocname, $isUser = true) {
		$table = $this->getMapTable($isUser);
		$dn = $this->sanitizeDN($dn);

		$sqlAdjustment = '';
		$dbtype = \OCP\Config::getSystemValue('dbtype');
		if($dbtype == 'mysql') {
			$sqlAdjustment = 'FROM DUAL';
		}

		$insert = \OCP\DB::prepare('
			INSERT INTO `'.$table.'` (`ldap_dn`, `owncloud_name`, `directory_uuid`)
				SELECT ?,?,?
				'.$sqlAdjustment.'
				WHERE NOT EXISTS (
					SELECT 1
					FROM `'.$table.'`
					WHERE `ldap_dn` = ?
						OR `owncloud_name` = ?)
		');

		//feed the DB
		$res = $insert->execute(array($dn, $ocname, $this->getUUID($dn), $dn, $ocname));

		if(\OCP\DB::isError($res)) {
			return false;
		}

		$insRows = $res->numRows();

		if($insRows == 0) {
			return false;
		}

		return true;
	}

	public function fetchListOfUsers($filter, $attr) {
		return $this->fetchList($this->searchUsers($filter, $attr), (count($attr) > 1));
	}

	public function fetchListOfGroups($filter, $attr) {
		return $this->fetchList($this->searchGroups($filter, $attr), (count($attr) > 1));
	}

	private function fetchList($list, $manyAttributes) {
		if(is_array($list)) {
			if($manyAttributes) {
				return $list;
			} else {
				return array_unique($list, SORT_LOCALE_STRING);
			}
		}

		//error cause actually, maybe throw an exception in future.
		return array();
	}

	/**
	 * @brief executes an LDAP search, optimized for Users
	 * @param $filter the LDAP filter for the search
	 * @param $attr optional, when a certain attribute shall be filtered out
	 * @returns array with the search result
	 *
	 * Executes an LDAP search
	 */
	public function searchUsers($filter, $attr = null) {
		return $this->search($filter, $this->connection->ldapBaseUsers, $attr);
	}

	/**
	 * @brief executes an LDAP search, optimized for Groups
	 * @param $filter the LDAP filter for the search
	 * @param $attr optional, when a certain attribute shall be filtered out
	 * @returns array with the search result
	 *
	 * Executes an LDAP search
	 */
	public function searchGroups($filter, $attr = null) {
		return $this->search($filter, $this->connection->ldapBaseGroups, $attr);
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
	private function search($filter, $base, $attr = null) {
		if(!is_null($attr) && !is_array($attr)) {
			$attr = array(mb_strtolower($attr, 'UTF-8'));
		}

		// See if we have a resource
		$link_resource = $this->connection->getConnectionResource();
		if(is_resource($link_resource)) {
			$sr = ldap_search($link_resource, $base, $filter, $attr);
			$findings = ldap_get_entries($link_resource, $sr );

			// if we're here, probably no connection resource is returned.
			// to make ownCloud behave nicely, we simply give back an empty array.
			if(is_null($findings)) {
				return array();
			}
		} else {
			// Seems like we didn't find any resource.
			// Return an empty array just like before.
			\OCP\Util::writeLog('user_ldap', 'Could not search, because resource is missing.', \OCP\Util::DEBUG);
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
				if(!is_array($item)) {
					continue;
				}
				$item = \OCP\Util::mb_array_change_key_case($item, MB_CASE_LOWER, 'UTF-8');

				if($multiarray) {
					foreach($attr as $key) {
						$key = mb_strtolower($key, 'UTF-8');
						if(isset($item[$key])) {
							if($key != 'dn') {
								$selection[$i][$key] = $this->resemblesDN($key) ? $this->sanitizeDN($item[$key][0]) : $item[$key][0];
							} else {
								$selection[$i][$key] = $this->sanitizeDN($item[$key]);
							}
						}

					}
					$i++;
				} else {
					//tribute to case insensitivity
					$key = mb_strtolower($attr[0], 'UTF-8');

					if(isset($item[$key])) {
						if($this->resemblesDN($key)) {
							$selection[] = $this->sanitizeDN($item[$key]);
						} else {
							$selection[] = $item[$key];
						}
					}
				}
			}
			return $selection;
		}
		return $findings;
	}

	public function sanitizeUsername($name) {
		if($this->connection->ldapIgnoreNamingRules) {
			return $name;
		}

		// Translitaration
		//latin characters to ASCII
		$name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);

		//REPLACEMENTS
		$name = \OCP\Util::mb_str_replace(' ', '_', $name, 'UTF-8');

		//every remaining unallowed characters will be removed
		$name = preg_replace('/[^a-zA-Z0-9_.@-]/u', '', $name);

		return $name;
	}

	/**
	 * @brief combines the input filters with AND
	 * @param $filters array, the filters to connect
	 * @returns the combined filter
	 *
	 * Combines Filter arguments with AND
	 */
	public function combineFilterWithAnd($filters) {
		return $this->combineFilter($filters, '&');
	}

	/**
	 * @brief combines the input filters with AND
	 * @param $filters array, the filters to connect
	 * @returns the combined filter
	 *
	 * Combines Filter arguments with AND
	 */
	public function combineFilterWithOr($filters) {
		return $this->combineFilter($filters, '|');
	}

	/**
	 * @brief combines the input filters with given operator
	 * @param $filters array, the filters to connect
	 * @param $operator either & or |
	 * @returns the combined filter
	 *
	 * Combines Filter arguments with AND
	 */
	private function combineFilter($filters, $operator) {
		$combinedFilter = '('.$operator;
		foreach($filters as $filter) {
		    if($filter[0] != '(') {
				$filter = '('.$filter.')';
		    }
		    $combinedFilter.=$filter;
		}
		$combinedFilter.=')';
		return $combinedFilter;
	}

	public function areCredentialsValid($name, $password) {
		$testConnection = clone $this->connection;
		$credentials = array(
			'ldapAgentName' => $name,
			'ldapAgentPassword' => $password
		);
		if(!$testConnection->setConfiguration($credentials)) {
			return false;
		}
		return $testConnection->bind();
	}

	/**
	 * @brief auto-detects the directory's UUID attribute
	 * @param $dn a known DN used to check against
	 * @param $force the detection should be run, even if it is not set to auto
	 * @returns true on success, false otherwise
	 */
	private function detectUuidAttribute($dn, $force = false) {
		if(($this->connection->ldapUuidAttribute != 'auto') && !$force) {
			return true;
		}

		//for now, supported (known) attributes are entryUUID, nsuniqueid, objectGUID
		$testAttributes = array('entryuuid', 'nsuniqueid', 'objectguid');

		foreach($testAttributes as $attribute) {
			\OCP\Util::writeLog('user_ldap', 'Testing '.$attribute.' as UUID attr', \OCP\Util::DEBUG);

		    $value = $this->readAttribute($dn, $attribute);
		    if(is_array($value) && isset($value[0]) && !empty($value[0])) {
				\OCP\Util::writeLog('user_ldap', 'Setting '.$attribute.' as UUID attr', \OCP\Util::DEBUG);
				$this->connection->ldapUuidAttribute = $attribute;
				return true;
		    }
		    \OCP\Util::writeLog('user_ldap', 'The looked for uuid attr is not '.$attribute.', result was '.print_r($value,true), \OCP\Util::DEBUG);
		}

		return false;
	}

	public function getUUID($dn) {
		if($this->detectUuidAttribute($dn)) {
			$uuid = $this->readAttribute($dn, $this->connection->ldapUuidAttribute);
			if(!is_array($uuid) && $this->connection->ldapOverrideUuidAttribute) {
				$this->detectUuidAttribute($dn, true);
				$uuid = $this->readAttribute($dn, $this->connection->ldapUuidAttribute);
			}
			if(is_array($uuid) && isset($uuid[0]) && !empty($uuid[0])) {
				$uuid = $uuid[0];
			} else {
				$uuid = false;
			}
		} else {
			$uuid = false;
		}
		return $uuid;
	}
}