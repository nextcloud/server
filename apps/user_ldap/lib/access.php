<?php

/**
 * ownCloud – LDAP Access
 *
 * @author Arthur Schiwon
 * @copyright 2012, 2013 Arthur Schiwon blizzz@owncloud.com
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
	//never ever check this var directly, always use getPagedSearchResultState
	protected $pagedSearchedSuccessful;

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
	 *        if empty, just check the record's existence
	 * @returns an array of values on success or an empty
	 *          array if $attr is empty, false otherwise
	 *
	 * Reads an attribute from an LDAP entry or check if entry exists
	 */
	public function readAttribute($dn, $attr, $filter = 'objectClass=*') {
		if(!$this->checkConnection()) {
			\OCP\Util::writeLog('user_ldap',
				'No LDAP Connector assigned, access impossible for readAttribute.',
				\OCP\Util::WARN);
			return false;
		}
		$cr = $this->connection->getConnectionResource();
		if(!is_resource($cr)) {
			//LDAP not available
			\OCP\Util::writeLog('user_ldap', 'LDAP resource not available.', \OCP\Util::DEBUG);
			return false;
		}
		$dn = $this->DNasBaseParameter($dn);
		$rr = @ldap_read($cr, $dn, $filter, array($attr));
		if(!is_resource($rr)) {
			if(!empty($attr)) {
				//do not throw this message on userExists check, irritates
				\OCP\Util::writeLog('user_ldap', 'readAttribute failed for DN '.$dn, \OCP\Util::DEBUG);
			}
			//in case an error occurs , e.g. object does not exist
			return false;
		}
		if (empty($attr)) {
			\OCP\Util::writeLog('user_ldap', 'readAttribute: '.$dn.' found', \OCP\Util::DEBUG);
			return array();
		}
		$er = ldap_first_entry($cr, $rr);
		if(!is_resource($er)) {
			//did not match the filter, return false
			return false;
		}
		//LDAP attributes are not case sensitive
		$result = \OCP\Util::mb_array_change_key_case(ldap_get_attributes($cr, $er), MB_CASE_LOWER, 'UTF-8');
		$attr = mb_strtolower($attr, 'UTF-8');

		if(isset($result[$attr]) && $result[$attr]['count'] > 0) {
			$values = array();
			for($i=0;$i<$result[$attr]['count'];$i++) {
				if($this->resemblesDN($attr)) {
					$values[] = $this->sanitizeDN($result[$attr][$i]);
				} elseif(strtolower($attr) == 'objectguid' || strtolower($attr) == 'guid') {
					$values[] = $this->convertObjectGUID2Str($result[$attr][$i]);
				} else {
					$values[] = $result[$attr][$i];
				}
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
		//treating multiple base DNs
		if(is_array($dn)) {
			$result = array();
			foreach($dn as $singleDN) {
			    $result[] = $this->sanitizeDN($singleDN);
			}
			return $result;
		}

		//OID sometimes gives back DNs with whitespace after the comma
		// a la "uid=foo, cn=bar, dn=..." We need to tackle this!
		$dn = preg_replace('/([^\\\]),(\s+)/u', '\1,', $dn);

		//make comparisons and everything work
		$dn = mb_strtolower($dn, 'UTF-8');

		//escape DN values according to RFC 2253 – this is already done by ldap_explode_dn
		//to use the DN in search filters, \ needs to be escaped to \5c additionally
		//to use them in bases, we convert them back to simple backslashes in readAttribute()
		$replacements = array(
			'\,' => '\5c2C',
			'\=' => '\5c3D',
			'\+' => '\5c2B',
			'\<' => '\5c3C',
			'\>' => '\5c3E',
			'\;' => '\5c3B',
			'\"' => '\5c22',
			'\#' => '\5c23',
			'('  => '\28',
			')'  => '\29',
			'*'  => '\2A',
		);
		$dn = str_replace(array_keys($replacements), array_values($replacements), $dn);

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
		$dn = $this->ocname2dn($name, false);

		if($dn) {
			return $dn;
		}

		return false;
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
	 * returns the internal ownCloud name for the given LDAP DN of the
	 * group, false on DN outside of search DN or failure
	 */
	public function dn2groupname($dn, $ldapname = null) {
		//To avoid bypassing the base DN settings under certain circumstances
		//with the group support, check whether the provided DN matches one of
		//the given Bases
		if(!$this->isDNPartOfBase($dn, $this->connection->ldapBaseGroups)) {
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
		//To avoid bypassing the base DN settings under certain circumstances
		//with the group support, check whether the provided DN matches one of
		//the given Bases
		if(!$this->isDNPartOfBase($dn, $this->connection->ldapBaseUsers)) {
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
		$table = $this->getMapTable($isUser);
		if($isUser) {
			$fncFindMappedName = 'findMappedUser';
			$nameAttribute = $this->connection->ldapUserDisplayName;
		} else {
			$fncFindMappedName = 'findMappedGroup';
			$nameAttribute = $this->connection->ldapGroupDisplayName;
		}

		//let's try to retrieve the ownCloud name from the mappings table
		$ocname = $this->$fncFindMappedName($dn);
		if($ocname) {
			return $ocname;
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
		} else {
			//If the UUID can't be detected something is foul.
			\OCP\Util::writeLog('user_ldap', 'Cannot determine UUID for '.$dn.'. Skipping.', \OCP\Util::INFO);
			return false;
		}

		if(is_null($ldapname)) {
			$ldapname = $this->readAttribute($dn, $nameAttribute);
			if(!isset($ldapname[0]) && empty($ldapname[0])) {
				\OCP\Util::writeLog('user_ldap', 'No or empty name for '.$dn.'.', \OCP\Util::INFO);
				return false;
			}
			$ldapname = $ldapname[0];
		}
		$intname = $isUser ? $this->sanitizeUsername($uuid) : $this->sanitizeUsername($ldapname);

		//a new user/group! Add it only if it doesn't conflict with other backend's users or existing groups
		//disabling Cache is required to avoid that the new user is cached as not-existing in fooExists check
		$originalTTL = $this->connection->ldapCacheTTL;
		$this->connection->setConfiguration(array('ldapCacheTTL' => 0));
		if(($isUser && !\OCP\User::userExists($intname))
			|| (!$isUser && !\OC_Group::groupExists($intname))) {
			if($this->mapComponent($dn, $intname, $isUser)) {
				$this->connection->setConfiguration(array('ldapCacheTTL' => $originalTTL));
				return $intname;
			}
		}
		$this->connection->setConfiguration(array('ldapCacheTTL' => $originalTTL));

		$altname = $this->createAltInternalOwnCloudName($intname, $isUser);
		if($this->mapComponent($dn, $altname, $isUser)) {
			return $altname;
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

	private function findMappedUser($dn) {
		static $query = null;
		if(is_null($query)) {
			$query = \OCP\DB::prepare('
				SELECT `owncloud_name`
				FROM `'.$this->getMapTable(true).'`
				WHERE `ldap_dn` = ?'
			);
		}
		$res = $query->execute(array($dn))->fetchOne();
		if($res) {
			return  $res;
		}
		return false;
	}

	private function findMappedGroup($dn) {
		static $query = null;
		if(is_null($query)) {
			$query = \OCP\DB::prepare('
					SELECT `owncloud_name`
					FROM `'.$this->getMapTable(false).'`
					WHERE `ldap_dn` = ?'
			);
		}
		$res = $query->execute(array($dn))->fetchOne();
		if($res) {
			return  $res;
		}
		return false;
	}


	private function ldap2ownCloudNames($ldapObjects, $isUsers) {
		if($isUsers) {
			$nameAttribute = $this->connection->ldapUserDisplayName;
		} else {
			$nameAttribute = $this->connection->ldapGroupDisplayName;
		}
		$ownCloudNames = array();

		foreach($ldapObjects as $ldapObject) {
			$nameByLDAP = isset($ldapObject[$nameAttribute]) ? $ldapObject[$nameAttribute] : null;
			$ocname = $this->dn2ocname($ldapObject['dn'], $nameByLDAP, $isUsers);
			if($ocname) {
				$ownCloudNames[] = $ocname;
			}
			continue;
		}
		return $ownCloudNames;
	}

	/**
	 * @brief creates a unique name for internal ownCloud use for users. Don't call it directly.
	 * @param $name the display name of the object
	 * @returns string with with the name to use in ownCloud or false if unsuccessful
	 *
	 * Instead of using this method directly, call
	 * createAltInternalOwnCloudName($name, true)
	 */
	private function _createAltInternalOwnCloudNameForUsers($name) {
		$attempts = 0;
		//while loop is just a precaution. If a name is not generated within
		//20 attempts, something else is very wrong. Avoids infinite loop.
		while($attempts < 20){
			$altName = $name . '_' . uniqid();
			if(\OCP\User::userExists($altName)) {
				return $altName;
			}
			$attempts++;
		}
		return false;
	}

	/**
	 * @brief creates a unique name for internal ownCloud use for groups. Don't call it directly.
	 * @param $name the display name of the object
	 * @returns string with with the name to use in ownCloud or false if unsuccessful.
	 *
	 * Instead of using this method directly, call
	 * createAltInternalOwnCloudName($name, false)
	 *
	 * Group names are also used as display names, so we do a sequential
	 * numbering, e.g. Developers_42 when there are 41 other groups called
	 * "Developers"
	 */
	private function _createAltInternalOwnCloudNameForGroups($name) {
		$query = \OCP\DB::prepare('
			SELECT `owncloud_name`
			FROM `'.$this->getMapTable(false).'`
			WHERE `owncloud_name` LIKE ?
		');

		$usedNames = array();
		$res = $query->execute(array($name.'_%'));
		while($row = $res->fetchRow()) {
			$usedNames[] = $row['owncloud_name'];
		}
		if(!($usedNames) || count($usedNames) == 0) {
			$lastNo = 1; //will become name_2
		} else {
			natsort($usedNames);
			$lastname = array_pop($usedNames);
			$lastNo = intval(substr($lastname, strrpos($lastname, '_') + 1));
		}
		$altName = $name.'_'.strval($lastNo+1);
		unset($usedNames);

		$attempts = 1;
		while($attempts < 21){
			//Pro forma check to be really sure it is unique
			//while loop is just a precaution. If a name is not generated within
			//20 attempts, something else is very wrong. Avoids infinite loop.
			if(!\OC_Group::groupExists($altName)) {
				return $altName;
			}
			$altName = $name . '_' . $lastNo + $attempts;
			$attempts++;
		}
		return false;
	}

	/**
	 * @brief creates a unique name for internal ownCloud use.
	 * @param $name the display name of the object
	 * @param $isUser boolean, whether name should be created for a user (true) or a group (false)
	 * @returns string with with the name to use in ownCloud or false if unsuccessful
	 */
	private function createAltInternalOwnCloudName($name, $isUser) {
		$originalTTL = $this->connection->ldapCacheTTL;
		$this->connection->setConfiguration(array('ldapCacheTTL' => 0));
		if($isUser) {
			$altName = $this->_createAltInternalOwnCloudNameForUsers($name);
		} else {
			$altName = $this->_createAltInternalOwnCloudNameForGroups($name);
		}
		$this->connection->setConfiguration(array('ldapCacheTTL' => $originalTTL));

		return $altName;
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

	public function fetchListOfUsers($filter, $attr, $limit = null, $offset = null) {
		return $this->fetchList($this->searchUsers($filter, $attr, $limit, $offset), (count($attr) > 1));
	}

	public function fetchListOfGroups($filter, $attr, $limit = null, $offset = null) {
		return $this->fetchList($this->searchGroups($filter, $attr, $limit, $offset), (count($attr) > 1));
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
	public function searchUsers($filter, $attr = null, $limit = null, $offset = null) {
		return $this->search($filter, $this->connection->ldapBaseUsers, $attr, $limit, $offset);
	}

	/**
	 * @brief executes an LDAP search, optimized for Groups
	 * @param $filter the LDAP filter for the search
	 * @param $attr optional, when a certain attribute shall be filtered out
	 * @returns array with the search result
	 *
	 * Executes an LDAP search
	 */
	public function searchGroups($filter, $attr = null, $limit = null, $offset = null) {
		return $this->search($filter, $this->connection->ldapBaseGroups, $attr, $limit, $offset);
	}

	/**
	 * @brief executes an LDAP search
	 * @param $filter the LDAP filter for the search
	 * @param $base an array containing the LDAP subtree(s) that shall be searched
	 * @param $attr optional, array, one or more attributes that shall be
	 * retrieved. Results will according to the order in the array.
	 * @returns array with the search result
	 *
	 * Executes an LDAP search
	 */
	private function search($filter, $base, $attr = null, $limit = null, $offset = null, $skipHandling = false) {
		if(!is_null($attr) && !is_array($attr)) {
			$attr = array(mb_strtolower($attr, 'UTF-8'));
		}

		// See if we have a resource, in case not cancel with message
		$link_resource = $this->connection->getConnectionResource();
		if(!is_resource($link_resource)) {
			// Seems like we didn't find any resource.
			// Return an empty array just like before.
			\OCP\Util::writeLog('user_ldap', 'Could not search, because resource is missing.', \OCP\Util::DEBUG);
			return array();
		}

		//check wether paged search should be attempted
		$pagedSearchOK = $this->initPagedSearch($filter, $base, $attr, $limit, $offset);

		$linkResources = array_pad(array(), count($base), $link_resource);
		$sr = ldap_search($linkResources, $base, $filter, $attr);
		$error = ldap_errno($link_resource);
		if(!is_array($sr) || $error != 0) {
			\OCP\Util::writeLog('user_ldap',
				'Error when searching: '.ldap_error($link_resource).' code '.ldap_errno($link_resource),
				\OCP\Util::ERROR);
			\OCP\Util::writeLog('user_ldap', 'Attempt for Paging?  '.print_r($pagedSearchOK, true), \OCP\Util::ERROR);
			return array();
		}

		// Do the server-side sorting
		foreach(array_reverse($attr) as $sortAttr){
			foreach($sr as $searchResource) {
				ldap_sort($link_resource, $searchResource, $sortAttr);
			}
		}

		$findings = array();
		foreach($sr as $key => $res) {
		    $findings = array_merge($findings, ldap_get_entries($link_resource, $res ));
		}
		if($pagedSearchOK) {
			\OCP\Util::writeLog('user_ldap', 'Paged search successful', \OCP\Util::INFO);
			foreach($sr as $key => $res) {
				$cookie = null;
				if(ldap_control_paged_result_response($link_resource, $res, $cookie)) {
					\OCP\Util::writeLog('user_ldap', 'Set paged search cookie', \OCP\Util::INFO);
					$this->setPagedResultCookie($base[$key], $filter, $limit, $offset, $cookie);
				}
			}

			//browsing through prior pages to get the cookie for the new one
			if($skipHandling) {
				return;
			}
			// if count is bigger, then the server does not support
			// paged search. Instead, he did a normal search. We set a
			// flag here, so the callee knows how to deal with it.
			if($findings['count'] <= $limit) {
				$this->pagedSearchedSuccessful = true;
			}
		} else {
			if(!is_null($limit)) {
				\OCP\Util::writeLog('user_ldap', 'Paged search failed :(', \OCP\Util::INFO);
			}
		}

		// if we're here, probably no connection resource is returned.
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
				if(!is_array($item)) {
					continue;
				}
				$item = \OCP\Util::mb_array_change_key_case($item, MB_CASE_LOWER, 'UTF-8');

				if($multiarray) {
					foreach($attr as $key) {
						$key = mb_strtolower($key, 'UTF-8');
						if(isset($item[$key])) {
							if($key != 'dn') {
								$selection[$i][$key] = $this->resemblesDN($key) ?
									$this->sanitizeDN($item[$key][0])
									: $item[$key][0];
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
			$findings = $selection;
		}
		//we slice the findings, when
		//a) paged search insuccessful, though attempted
		//b) no paged search, but limit set
		if((!$this->pagedSearchedSuccessful
			&& $pagedSearchOK)
			|| (
				!$pagedSearchOK
				&& !is_null($limit)
			)
		) {
			$findings = array_slice($findings, intval($offset), $limit);
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

	/**
	 * @brief creates a filter part for to perfrom search for users
	 * @param string $search the search term
	 * @return string the final filter part to use in LDAP searches
	 */
	public function getFilterPartForUserSearch($search) {
		return $this->getFilterPartForSearch($search,
			$this->connection->ldapAttributesForUserSearch,
			$this->connection->ldapUserDisplayName);
	}

	/**
	 * @brief creates a filter part for to perfrom search for groups
	 * @param string $search the search term
	 * @return string the final filter part to use in LDAP searches
	 */
	public function getFilterPartForGroupSearch($search) {
		return $this->getFilterPartForSearch($search,
			$this->connection->ldapAttributesForGroupSearch,
			$this->connection->ldapGroupDisplayName);
	}

	/**
	 * @brief creates a filter part for searches
	 * @param string $search the search term
	 * @param string $fallbackAttribute a fallback attribute in case the user
	 * did not define search attributes. Typically the display name attribute.
	 * @returns string the final filter part to use in LDAP searches
	 */
	private function getFilterPartForSearch($search, $searchAttributes, $fallbackAttribute) {
		$filter = array();
		$search = empty($search) ? '*' : '*'.$search.'*';
		if(!is_array($searchAttributes) || count($searchAttributes) == 0) {
			if(empty($fallbackAttribute)) {
				return '';
			}
			$filter[] = $fallbackAttribute . '=' . $search;
		} else {
			foreach($searchAttributes as $attribute) {
				$filter[] = $attribute . '=' . $search;
			}
		}
		if(count($filter) == 1) {
			return '('.$filter[0].')';
		}
		return $this->combineFilterWithOr($filter);
	}

	public function areCredentialsValid($name, $password) {
		$name = $this->DNasBaseParameter($name);
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
		$testAttributes = array('entryuuid', 'nsuniqueid', 'objectguid', 'guid');

		foreach($testAttributes as $attribute) {
			\OCP\Util::writeLog('user_ldap', 'Testing '.$attribute.' as UUID attr', \OCP\Util::DEBUG);

			$value = $this->readAttribute($dn, $attribute);
			if(is_array($value) && isset($value[0]) && !empty($value[0])) {
				\OCP\Util::writeLog('user_ldap', 'Setting '.$attribute.' as UUID attr', \OCP\Util::DEBUG);
				$this->connection->ldapUuidAttribute = $attribute;
				return true;
			}
			\OCP\Util::writeLog('user_ldap',
				'The looked for uuid attr is not '.$attribute.', result was '.print_r($value, true),
				\OCP\Util::DEBUG);
		}

		return false;
	}

	public function getUUID($dn) {
		if($this->detectUuidAttribute($dn)) {
			\OCP\Util::writeLog('user_ldap',
				'UUID Checking \ UUID for '.$dn.' using '. $this->connection->ldapUuidAttribute,
				\OCP\Util::DEBUG);
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

	/**
	 * @brief converts a binary ObjectGUID into a string representation
	 * @param $oguid the ObjectGUID in it's binary form as retrieved from AD
	 * @returns String
	 *
	 * converts a binary ObjectGUID into a string representation
	 * http://www.php.net/manual/en/function.ldap-get-values-len.php#73198
	 */
	private function convertObjectGUID2Str($oguid) {
		$hex_guid = bin2hex($oguid);
		$hex_guid_to_guid_str = '';
		for($k = 1; $k <= 4; ++$k) {
			$hex_guid_to_guid_str .= substr($hex_guid, 8 - 2 * $k, 2);
		}
		$hex_guid_to_guid_str .= '-';
		for($k = 1; $k <= 2; ++$k) {
			$hex_guid_to_guid_str .= substr($hex_guid, 12 - 2 * $k, 2);
		}
		$hex_guid_to_guid_str .= '-';
		for($k = 1; $k <= 2; ++$k) {
			$hex_guid_to_guid_str .= substr($hex_guid, 16 - 2 * $k, 2);
		}
		$hex_guid_to_guid_str .= '-' . substr($hex_guid, 16, 4);
		$hex_guid_to_guid_str .= '-' . substr($hex_guid, 20);

		return strtoupper($hex_guid_to_guid_str);
	}

	/**
	 * @brief converts a stored DN so it can be used as base parameter for LDAP queries
	 * @param $dn the DN
	 * @returns String
	 *
	 * converts a stored DN so it can be used as base parameter for LDAP queries
	 * internally we store them for usage in LDAP filters
	 */
	private function DNasBaseParameter($dn) {
		return str_replace('\\5c', '\\', $dn);
	}

	/**
	 * @brief checks if the given DN is part of the given base DN(s)
	 * @param $dn the DN
	 * @param $bases array containing the allowed base DN or DNs
	 * @returns Boolean
	 */
	private function isDNPartOfBase($dn, $bases) {
		$bases = $this->sanitizeDN($bases);
		foreach($bases as $base) {
			$belongsToBase = true;
			if(mb_strripos($dn, $base, 0, 'UTF-8') !== (mb_strlen($dn, 'UTF-8')-mb_strlen($base))) {
				$belongsToBase = false;
			}
			if($belongsToBase) {
				break;
			}
		}
		return $belongsToBase;
	}

	/**
	 * @brief get a cookie for the next LDAP paged search
	 * @param $base a string with the base DN for the search
	 * @param $filter the search filter to identify the correct search
	 * @param $limit the limit (or 'pageSize'), to identify the correct search well
	 * @param $offset the offset for the new search to identify the correct search really good
	 * @returns string containing the key or empty if none is cached
	 */
	private function getPagedResultCookie($base, $filter, $limit, $offset) {
		if($offset == 0) {
			return '';
		}
		$offset -= $limit;
		//we work with cache here
		$cachekey = 'lc' . crc32($base) . '-' . crc32($filter) . '-' . $limit . '-' . $offset;
		$cookie = $this->connection->getFromCache($cachekey);
		if(is_null($cookie)) {
			$cookie = '';
		}
		return $cookie;
	}

	/**
	 * @brief set a cookie for LDAP paged search run
	 * @param $base a string with the base DN for the search
	 * @param $filter the search filter to identify the correct search
	 * @param $limit the limit (or 'pageSize'), to identify the correct search well
	 * @param $offset the offset for the run search to identify the correct search really good
	 * @param $cookie string containing the cookie returned by ldap_control_paged_result_response
	 * @return void
	 */
	private function setPagedResultCookie($base, $filter, $limit, $offset, $cookie) {
		if(!empty($cookie)) {
			$cachekey = 'lc' . dechex(crc32($base)) . '-' . dechex(crc32($filter)) . '-' .$limit . '-' . $offset;
			$cookie = $this->connection->writeToCache($cachekey, $cookie);
		}
	}

	/**
	 * @brief check wether the most recent paged search was successful. It flushed the state var. Use it always after a possible paged search.
	 * @return true on success, null or false otherwise
	 */
	public function getPagedSearchResultState() {
		$result = $this->pagedSearchedSuccessful;
		$this->pagedSearchedSuccessful = null;
		return $result;
	}


	/**
	 * @brief prepares a paged search, if possible
	 * @param $filter the LDAP filter for the search
	 * @param $bases an array containing the LDAP subtree(s) that shall be searched
	 * @param $attr optional, when a certain attribute shall be filtered outside
	 * @param $limit
	 * @param $offset
	 *
	 */
	private function initPagedSearch($filter, $bases, $attr, $limit, $offset) {
		$pagedSearchOK = false;
		if($this->connection->hasPagedResultSupport && !is_null($limit)) {
			$offset = intval($offset); //can be null
			\OCP\Util::writeLog('user_ldap',
				'initializing paged search for  Filter'.$filter.' base '.print_r($bases, true)
				.' attr '.print_r($attr, true). ' limit ' .$limit.' offset '.$offset,
				\OCP\Util::INFO);
			//get the cookie from the search for the previous search, required by LDAP
			foreach($bases as $base) {

				$cookie = $this->getPagedResultCookie($base, $filter, $limit, $offset);
				if(empty($cookie) && ($offset > 0)) {
					// no cookie known, although the offset is not 0. Maybe cache run out. We need
					// to start all over *sigh* (btw, Dear Reader, did you need LDAP paged
					// searching was designed by MSFT?)
					$reOffset = ($offset - $limit) < 0 ? 0 : $offset - $limit;
					//a bit recursive, $offset of 0 is the exit
					\OCP\Util::writeLog('user_ldap', 'Looking for cookie L/O '.$limit.'/'.$reOffset, \OCP\Util::INFO);
					$this->search($filter, array($base), $attr, $limit, $reOffset, true);
					$cookie = $this->getPagedResultCookie($base, $filter, $limit, $offset);
					//still no cookie? obviously, the server does not like us. Let's skip paging efforts.
					//TODO: remember this, probably does not change in the next request...
					if(empty($cookie)) {
						$cookie = null;
					}
				}
				if(!is_null($cookie)) {
					if($offset > 0) {
						\OCP\Util::writeLog('user_ldap', 'Cookie '.$cookie, \OCP\Util::INFO);
					}
					$pagedSearchOK = ldap_control_paged_result($this->connection->getConnectionResource(),
						$limit, false, $cookie);
					if(!$pagedSearchOK) {
						return false;
					}
					\OCP\Util::writeLog('user_ldap', 'Ready for a paged search', \OCP\Util::INFO);
				} else {
					\OCP\Util::writeLog('user_ldap',
						'No paged search for us, Cpt., Limit '.$limit.' Offset '.$offset,
						\OCP\Util::INFO);
				}

			}
		}

		return $pagedSearchOK;
	}

}
