<?php
/**
 * @author Alexander Bergolth <leo@strike.wu.ac.at>
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Benjamin Diele <benjamin@diele.be>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lorenzo M. Catucci <lorenzo@sancho.ccd.uniroma2.it>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Lyonel Vincent <lyonel@ezix.org>
 * @author Mario Kolling <mario.kolling@serpro.gov.br>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Nicolas Grekas <nicolas.grekas@gmail.com>
 * @author Ralph Krimmel <rkrimme1@gwdg.de>
 * @author Renaud Fortier <Renaud.Fortier@fsaa.ulaval.ca>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\User_LDAP;

use OCA\User_LDAP\User\IUserTools;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\User\OfflineUser;
use OCA\User_LDAP\Mapping\AbstractMapping;

/**
 * Class Access
 * @package OCA\User_LDAP
 */
class Access extends LDAPUtility implements IUserTools {
	/**
	 * @var \OCA\User_LDAP\Connection
	 */
	public $connection;
	public $userManager;
	//never ever check this var directly, always use getPagedSearchResultState
	protected $pagedSearchedSuccessful;

	/**
	 * @var string[] $cookies an array of returned Paged Result cookies
	 */
	protected $cookies = array();

	/**
	 * @var string $lastCookie the last cookie returned from a Paged Results
	 * operation, defaults to an empty string
	 */
	protected $lastCookie = '';

	/**
	 * @var AbstractMapping $userMapper
	 */
	protected $userMapper;

	/**
	* @var AbstractMapping $userMapper
	*/
	protected $groupMapper;

	public function __construct(Connection $connection, ILDAPWrapper $ldap,
		Manager $userManager) {
		parent::__construct($ldap);
		$this->connection = $connection;
		$this->userManager = $userManager;
		$this->userManager->setLdapAccess($this);
	}

	/**
	 * sets the User Mapper
	 * @param AbstractMapping $mapper
	 */
	public function setUserMapper(AbstractMapping $mapper) {
		$this->userMapper = $mapper;
	}

	/**
	 * returns the User Mapper
	 * @throws \Exception
	 * @return AbstractMapping
	 */
	public function getUserMapper() {
		if(is_null($this->userMapper)) {
			throw new \Exception('UserMapper was not assigned to this Access instance.');
		}
		return $this->userMapper;
	}

	/**
	 * sets the Group Mapper
	 * @param AbstractMapping $mapper
	 */
	public function setGroupMapper(AbstractMapping $mapper) {
		$this->groupMapper = $mapper;
	}

	/**
	 * returns the Group Mapper
	 * @throws \Exception
	 * @return AbstractMapping
	 */
	public function getGroupMapper() {
		if(is_null($this->groupMapper)) {
			throw new \Exception('GroupMapper was not assigned to this Access instance.');
		}
		return $this->groupMapper;
	}

	/**
	 * @return bool
	 */
	private function checkConnection() {
		return ($this->connection instanceof Connection);
	}

	/**
	 * returns the Connection instance
	 * @return \OCA\User_LDAP\Connection
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * reads a given attribute for an LDAP record identified by a DN
	 * @param string $dn the record in question
	 * @param string $attr the attribute that shall be retrieved
	 *        if empty, just check the record's existence
	 * @param string $filter
	 * @return array|false an array of values on success or an empty
	 *          array if $attr is empty, false otherwise
	 */
	public function readAttribute($dn, $attr, $filter = 'objectClass=*') {
		if(!$this->checkConnection()) {
			\OCP\Util::writeLog('user_ldap',
				'No LDAP Connector assigned, access impossible for readAttribute.',
				\OCP\Util::WARN);
			return false;
		}
		$cr = $this->connection->getConnectionResource();
		if(!$this->ldap->isResource($cr)) {
			//LDAP not available
			\OCP\Util::writeLog('user_ldap', 'LDAP resource not available.', \OCP\Util::DEBUG);
			return false;
		}
		//Cancel possibly running Paged Results operation, otherwise we run in
		//LDAP protocol errors
		$this->abandonPagedSearch();
		// openLDAP requires that we init a new Paged Search. Not needed by AD,
		// but does not hurt either.
		$pagingSize = intval($this->connection->ldapPagingSize);
		// 0 won't result in replies, small numbers may leave out groups
		// (cf. #12306), 500 is default for paging and should work everywhere.
		$maxResults = $pagingSize > 20 ? $pagingSize : 500;
		$this->initPagedSearch($filter, array($dn), array($attr), $maxResults, 0);
		$dn = $this->DNasBaseParameter($dn);
		$rr = @$this->ldap->read($cr, $dn, $filter, array($attr));
		if(!$this->ldap->isResource($rr)) {
			if(!empty($attr)) {
				//do not throw this message on userExists check, irritates
				\OCP\Util::writeLog('user_ldap', 'readAttribute failed for DN '.$dn, \OCP\Util::DEBUG);
			}
			//in case an error occurs , e.g. object does not exist
			return false;
		}
		if (empty($attr) && ($filter === 'objectclass=*' || $this->ldap->countEntries($cr, $rr) === 1)) {
			\OCP\Util::writeLog('user_ldap', 'readAttribute: '.$dn.' found', \OCP\Util::DEBUG);
			return array();
		}
		$er = $this->ldap->firstEntry($cr, $rr);
		if(!$this->ldap->isResource($er)) {
			//did not match the filter, return false
			return false;
		}
		//LDAP attributes are not case sensitive
		$result = \OCP\Util::mb_array_change_key_case(
				$this->ldap->getAttributes($cr, $er), MB_CASE_LOWER, 'UTF-8');
		$attr = mb_strtolower($attr, 'UTF-8');

		if(isset($result[$attr]) && $result[$attr]['count'] > 0) {
			$values = array();
			for($i=0;$i<$result[$attr]['count'];$i++) {
				if($this->resemblesDN($attr)) {
					$values[] = $this->sanitizeDN($result[$attr][$i]);
				} elseif(strtolower($attr) === 'objectguid' || strtolower($attr) === 'guid') {
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
	 * checks whether the given attributes value is probably a DN
	 * @param string $attr the attribute in question
	 * @return boolean if so true, otherwise false
	 */
	private function resemblesDN($attr) {
		$resemblingAttributes = array(
			'dn',
			'uniquemember',
			'member',
			// memberOf is an "operational" attribute, without a definition in any RFC
			'memberof'
		);
		return in_array($attr, $resemblingAttributes);
	}

	/**
	 * checks whether the given string is probably a DN
	 * @param string $string
	 * @return boolean
	 */
	public function stringResemblesDN($string) {
		$r = $this->ldap->explodeDN($string, 0);
		// if exploding a DN succeeds and does not end up in
		// an empty array except for $r[count] being 0.
		return (is_array($r) && count($r) > 1);
	}

	/**
	 * sanitizes a DN received from the LDAP server
	 * @param array $dn the DN in question
	 * @return array the sanitized DN
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
	 * returns a DN-string that is cleaned from not domain parts, e.g.
	 * cn=foo,cn=bar,dc=foobar,dc=server,dc=org
	 * becomes dc=foobar,dc=server,dc=org
	 * @param string $dn
	 * @return string
	 */
	public function getDomainDNFromDN($dn) {
		$allParts = $this->ldap->explodeDN($dn, 0);
		if($allParts === false) {
			//not a valid DN
			return '';
		}
		$domainParts = array();
		$dcFound = false;
		foreach($allParts as $part) {
			if(!$dcFound && strpos($part, 'dc=') === 0) {
				$dcFound = true;
			}
			if($dcFound) {
				$domainParts[] = $part;
			}
		}
		$domainDN = implode(',', $domainParts);
		return $domainDN;
	}

	/**
	 * returns the LDAP DN for the given internal ownCloud name of the group
	 * @param string $name the ownCloud name in question
	 * @return string|false LDAP DN on success, otherwise false
	 */
	public function groupname2dn($name) {
		return $this->groupMapper->getDNByName($name);
	}

	/**
	 * returns the LDAP DN for the given internal ownCloud name of the user
	 * @param string $name the ownCloud name in question
	 * @return string|false with the LDAP DN on success, otherwise false
	 */
	public function username2dn($name) {
		$fdn = $this->userMapper->getDNByName($name);

		//Check whether the DN belongs to the Base, to avoid issues on multi-
		//server setups
		if(is_string($fdn) && $this->isDNPartOfBase($fdn, $this->connection->ldapBaseUsers)) {
			return $fdn;
		}

		return false;
	}

	/**
	public function ocname2dn($name, $isUser) {
	 * returns the internal ownCloud name for the given LDAP DN of the group, false on DN outside of search DN or failure
	 * @param string $fdn the dn of the group object
	 * @param string $ldapName optional, the display name of the object
	 * @return string|false with the name to use in ownCloud, false on DN outside of search DN
	 */
	public function dn2groupname($fdn, $ldapName = null) {
		//To avoid bypassing the base DN settings under certain circumstances
		//with the group support, check whether the provided DN matches one of
		//the given Bases
		if(!$this->isDNPartOfBase($fdn, $this->connection->ldapBaseGroups)) {
			return false;
		}

		return $this->dn2ocname($fdn, $ldapName, false);
	}

	/**
	 * accepts an array of group DNs and tests whether they match the user
	 * filter by doing read operations against the group entries. Returns an
	 * array of DNs that match the filter.
	 *
	 * @param string[] $groupDNs
	 * @return string[]
	 */
	public function groupsMatchFilter($groupDNs) {
		$validGroupDNs = [];
		foreach($groupDNs as $dn) {
			$cacheKey = 'groupsMatchFilter-'.$dn;
			$groupMatchFilter = $this->connection->getFromCache($cacheKey);
			if(!is_null($groupMatchFilter)) {
				if($groupMatchFilter) {
					$validGroupDNs[] = $dn;
				}
				continue;
			}

			// Check the base DN first. If this is not met already, we don't
			// need to ask the server at all.
			if(!$this->isDNPartOfBase($dn, $this->connection->ldapBaseGroups)) {
				$this->connection->writeToCache($cacheKey, false);
				continue;
			}

			$result = $this->readAttribute($dn, 'cn', $this->connection->ldapGroupFilter);
			if(is_array($result)) {
				$this->connection->writeToCache($cacheKey, true);
				$validGroupDNs[] = $dn;
			} else {
				$this->connection->writeToCache($cacheKey, false);
			}

		}
		return $validGroupDNs;
	}

	/**
	 * returns the internal ownCloud name for the given LDAP DN of the user, false on DN outside of search DN or failure
	 * @param string $dn the dn of the user object
	 * @param string $ldapName optional, the display name of the object
	 * @return string|false with with the name to use in ownCloud
	 */
	public function dn2username($fdn, $ldapName = null) {
		//To avoid bypassing the base DN settings under certain circumstances
		//with the group support, check whether the provided DN matches one of
		//the given Bases
		if(!$this->isDNPartOfBase($fdn, $this->connection->ldapBaseUsers)) {
			return false;
		}

		return $this->dn2ocname($fdn, $ldapName, true);
	}

	/**
	 * returns an internal ownCloud name for the given LDAP DN, false on DN outside of search DN
	 * @param string $dn the dn of the user object
	 * @param string $ldapName optional, the display name of the object
	 * @param bool $isUser optional, whether it is a user object (otherwise group assumed)
	 * @return string|false with with the name to use in ownCloud
	 */
	public function dn2ocname($fdn, $ldapName = null, $isUser = true) {
		if($isUser) {
			$mapper = $this->getUserMapper();
			$nameAttribute = $this->connection->ldapUserDisplayName;
		} else {
			$mapper = $this->getGroupMapper();
			$nameAttribute = $this->connection->ldapGroupDisplayName;
		}

		//let's try to retrieve the ownCloud name from the mappings table
		$ocName = $mapper->getNameByDN($fdn);
		if(is_string($ocName)) {
			return $ocName;
		}

		//second try: get the UUID and check if it is known. Then, update the DN and return the name.
		$uuid = $this->getUUID($fdn, $isUser);
		if(is_string($uuid)) {
			$ocName = $mapper->getNameByUUID($uuid);
			if(is_string($ocName)) {
				$mapper->setDNbyUUID($fdn, $uuid);
				return $ocName;
			}
		} else {
			//If the UUID can't be detected something is foul.
			\OCP\Util::writeLog('user_ldap', 'Cannot determine UUID for '.$fdn.'. Skipping.', \OCP\Util::INFO);
			return false;
		}

		if(is_null($ldapName)) {
			$ldapName = $this->readAttribute($fdn, $nameAttribute);
			if(!isset($ldapName[0]) && empty($ldapName[0])) {
				\OCP\Util::writeLog('user_ldap', 'No or empty name for '.$fdn.'.', \OCP\Util::INFO);
				return false;
			}
			$ldapName = $ldapName[0];
		}

		if($isUser) {
			$usernameAttribute = $this->connection->ldapExpertUsernameAttr;
			if(!empty($usernameAttribute)) {
				$username = $this->readAttribute($fdn, $usernameAttribute);
				$username = $username[0];
			} else {
				$username = $uuid;
			}
			$intName = $this->sanitizeUsername($username);
		} else {
			$intName = $ldapName;
		}

		//a new user/group! Add it only if it doesn't conflict with other backend's users or existing groups
		//disabling Cache is required to avoid that the new user is cached as not-existing in fooExists check
		//NOTE: mind, disabling cache affects only this instance! Using it
		// outside of core user management will still cache the user as non-existing.
		$originalTTL = $this->connection->ldapCacheTTL;
		$this->connection->setConfiguration(array('ldapCacheTTL' => 0));
		if(($isUser && !\OCP\User::userExists($intName))
			|| (!$isUser && !\OC_Group::groupExists($intName))) {
			if($mapper->map($fdn, $intName, $uuid)) {
				$this->connection->setConfiguration(array('ldapCacheTTL' => $originalTTL));
				return $intName;
			}
		}
		$this->connection->setConfiguration(array('ldapCacheTTL' => $originalTTL));

		$altName = $this->createAltInternalOwnCloudName($intName, $isUser);
		if(is_string($altName) && $mapper->map($fdn, $altName, $uuid)) {
			return $altName;
		}

		//if everything else did not help..
		\OCP\Util::writeLog('user_ldap', 'Could not create unique name for '.$fdn.'.', \OCP\Util::INFO);
		return false;
	}

	/**
	 * gives back the user names as they are used ownClod internally
	 * @param array $ldapUsers as returned by fetchList()
	 * @return array an array with the user names to use in ownCloud
	 *
	 * gives back the user names as they are used ownClod internally
	 */
	public function ownCloudUserNames($ldapUsers) {
		return $this->ldap2ownCloudNames($ldapUsers, true);
	}

	/**
	 * gives back the group names as they are used ownClod internally
	 * @param array $ldapGroups as returned by fetchList()
	 * @return array an array with the group names to use in ownCloud
	 *
	 * gives back the group names as they are used ownClod internally
	 */
	public function ownCloudGroupNames($ldapGroups) {
		return $this->ldap2ownCloudNames($ldapGroups, false);
	}

	/**
	 * @param array $ldapObjects as returned by fetchList()
	 * @param bool $isUsers
	 * @return array
	 */
	private function ldap2ownCloudNames($ldapObjects, $isUsers) {
		if($isUsers) {
			$nameAttribute = $this->connection->ldapUserDisplayName;
			$sndAttribute  = $this->connection->ldapUserDisplayName2;
		} else {
			$nameAttribute = $this->connection->ldapGroupDisplayName;
		}
		$ownCloudNames = array();

		foreach($ldapObjects as $ldapObject) {
			$nameByLDAP = null;
			if(    isset($ldapObject[$nameAttribute])
				&& is_array($ldapObject[$nameAttribute])
				&& isset($ldapObject[$nameAttribute][0])
			) {
				// might be set, but not necessarily. if so, we use it.
				$nameByLDAP = $ldapObject[$nameAttribute][0];
			}

			$ocName = $this->dn2ocname($ldapObject['dn'][0], $nameByLDAP, $isUsers);
			if($ocName) {
				$ownCloudNames[] = $ocName;
				if($isUsers) {
					//cache the user names so it does not need to be retrieved
					//again later (e.g. sharing dialogue).
					if(is_null($nameByLDAP)) {
						continue;
					}
					$sndName = isset($ldapObject[$sndAttribute][0])
						? $ldapObject[$sndAttribute][0] : '';
					$this->cacheUserDisplayName($ocName, $nameByLDAP, $sndName);
				}
			}
		}
		return $ownCloudNames;
	}

	/**
	 * caches the user display name
	 * @param string $ocName the internal ownCloud username
	 * @param string|false $home the home directory path
	 */
	public function cacheUserHome($ocName, $home) {
		$cacheKey = 'getHome'.$ocName;
		$this->connection->writeToCache($cacheKey, $home);
	}

	/**
	 * caches a user as existing
	 * @param string $ocName the internal ownCloud username
	 */
	public function cacheUserExists($ocName) {
		$this->connection->writeToCache('userExists'.$ocName, true);
	}

	/**
	 * caches the user display name
	 * @param string $ocName the internal ownCloud username
	 * @param string $displayName the display name
	 * @param string $displayName2 the second display name
	 */
	public function cacheUserDisplayName($ocName, $displayName, $displayName2 = '') {
		$user = $this->userManager->get($ocName);
		$displayName = $user->composeAndStoreDisplayName($displayName, $displayName2);
		$cacheKeyTrunk = 'getDisplayName';
		$this->connection->writeToCache($cacheKeyTrunk.$ocName, $displayName);
	}

	/**
	 * creates a unique name for internal ownCloud use for users. Don't call it directly.
	 * @param string $name the display name of the object
	 * @return string|false with with the name to use in ownCloud or false if unsuccessful
	 *
	 * Instead of using this method directly, call
	 * createAltInternalOwnCloudName($name, true)
	 */
	private function _createAltInternalOwnCloudNameForUsers($name) {
		$attempts = 0;
		//while loop is just a precaution. If a name is not generated within
		//20 attempts, something else is very wrong. Avoids infinite loop.
		while($attempts < 20){
			$altName = $name . '_' . rand(1000,9999);
			if(!\OCP\User::userExists($altName)) {
				return $altName;
			}
			$attempts++;
		}
		return false;
	}

	/**
	 * creates a unique name for internal ownCloud use for groups. Don't call it directly.
	 * @param string $name the display name of the object
	 * @return string|false with with the name to use in ownCloud or false if unsuccessful.
	 *
	 * Instead of using this method directly, call
	 * createAltInternalOwnCloudName($name, false)
	 *
	 * Group names are also used as display names, so we do a sequential
	 * numbering, e.g. Developers_42 when there are 41 other groups called
	 * "Developers"
	 */
	private function _createAltInternalOwnCloudNameForGroups($name) {
		$usedNames = $this->groupMapper->getNamesBySearch($name, "", '_%');
		if(!($usedNames) || count($usedNames) === 0) {
			$lastNo = 1; //will become name_2
		} else {
			natsort($usedNames);
			$lastName = array_pop($usedNames);
			$lastNo = intval(substr($lastName, strrpos($lastName, '_') + 1));
		}
		$altName = $name.'_'.strval($lastNo+1);
		unset($usedNames);

		$attempts = 1;
		while($attempts < 21){
			// Check to be really sure it is unique
			// while loop is just a precaution. If a name is not generated within
			// 20 attempts, something else is very wrong. Avoids infinite loop.
			if(!\OC_Group::groupExists($altName)) {
				return $altName;
			}
			$altName = $name . '_' . ($lastNo + $attempts);
			$attempts++;
		}
		return false;
	}

	/**
	 * creates a unique name for internal ownCloud use.
	 * @param string $name the display name of the object
	 * @param boolean $isUser whether name should be created for a user (true) or a group (false)
	 * @return string|false with with the name to use in ownCloud or false if unsuccessful
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
	 * fetches a list of users according to a provided loginName and utilizing
	 * the login filter.
	 *
	 * @param string $loginName
	 * @param array $attributes optional, list of attributes to read
	 * @return array
	 */
	public function fetchUsersByLoginName($loginName, $attributes = array('dn')) {
		$loginName = $this->escapeFilterPart($loginName);
		$filter = str_replace('%uid', $loginName, $this->connection->ldapLoginFilter);
		$users = $this->fetchListOfUsers($filter, $attributes);
		return $users;
	}

	/**
	 * counts the number of users according to a provided loginName and
	 * utilizing the login filter.
	 *
	 * @param string $loginName
	 * @return array
	 */
	public function countUsersByLoginName($loginName) {
		$loginName = $this->escapeFilterPart($loginName);
		$filter = str_replace('%uid', $loginName, $this->connection->ldapLoginFilter);
		$users = $this->countUsers($filter);
		return $users;
	}

	/**
	 * @param string $filter
	 * @param string|string[] $attr
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function fetchListOfUsers($filter, $attr, $limit = null, $offset = null) {
		$ldapRecords = $this->searchUsers($filter, $attr, $limit, $offset);
		$this->batchApplyUserAttributes($ldapRecords);
		return $this->fetchList($ldapRecords, (count($attr) > 1));
	}

	/**
	 * provided with an array of LDAP user records the method will fetch the
	 * user object and requests it to process the freshly fetched attributes and
	 * and their values
	 * @param array $ldapRecords
	 */
	public function batchApplyUserAttributes(array $ldapRecords){
		$displayNameAttribute = strtolower($this->connection->ldapUserDisplayName);
		foreach($ldapRecords as $userRecord) {
			if(!isset($userRecord[$displayNameAttribute])) {
				// displayName is obligatory
				continue;
			}
			$ocName  = $this->dn2ocname($userRecord['dn'][0]);
			if($ocName === false) {
				continue;
			}
			$this->cacheUserExists($ocName);
			$user = $this->userManager->get($ocName);
			if($user instanceof OfflineUser) {
				$user->unmark();
				$user = $this->userManager->get($ocName);
			}
			if ($user !== null) {
				$user->processAttributes($userRecord);
			} else {
				\OC::$server->getLogger()->debug(
					"The ldap user manager returned null for $ocName",
					['app'=>'user_ldap']
				);
			}
		}
	}

	/**
	 * @param string $filter
	 * @param string|string[] $attr
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function fetchListOfGroups($filter, $attr, $limit = null, $offset = null) {
		return $this->fetchList($this->searchGroups($filter, $attr, $limit, $offset), (count($attr) > 1));
	}

	/**
	 * @param array $list
	 * @param bool $manyAttributes
	 * @return array
	 */
	private function fetchList($list, $manyAttributes) {
		if(is_array($list)) {
			if($manyAttributes) {
				return $list;
			} else {
				$list = array_reduce($list, function($carry, $item) {
					$attribute = array_keys($item)[0];
					$carry[] = $item[$attribute][0];
					return $carry;
				}, array());
				return array_unique($list, SORT_LOCALE_STRING);
			}
		}

		//error cause actually, maybe throw an exception in future.
		return array();
	}

	/**
	 * executes an LDAP search, optimized for Users
	 * @param string $filter the LDAP filter for the search
	 * @param string|string[] $attr optional, when a certain attribute shall be filtered out
	 * @param integer $limit
	 * @param integer $offset
	 * @return array with the search result
	 *
	 * Executes an LDAP search
	 */
	public function searchUsers($filter, $attr = null, $limit = null, $offset = null) {
		return $this->search($filter, $this->connection->ldapBaseUsers, $attr, $limit, $offset);
	}

	/**
	 * @param string $filter
	 * @param string|string[] $attr
	 * @param int $limit
	 * @param int $offset
	 * @return false|int
	 */
	public function countUsers($filter, $attr = array('dn'), $limit = null, $offset = null) {
		return $this->count($filter, $this->connection->ldapBaseUsers, $attr, $limit, $offset);
	}

	/**
	 * executes an LDAP search, optimized for Groups
	 * @param string $filter the LDAP filter for the search
	 * @param string|string[] $attr optional, when a certain attribute shall be filtered out
	 * @param integer $limit
	 * @param integer $offset
	 * @return array with the search result
	 *
	 * Executes an LDAP search
	 */
	public function searchGroups($filter, $attr = null, $limit = null, $offset = null) {
		return $this->search($filter, $this->connection->ldapBaseGroups, $attr, $limit, $offset);
	}

	/**
	 * returns the number of available groups
	 * @param string $filter the LDAP search filter
	 * @param string[] $attr optional
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return int|bool
	 */
	public function countGroups($filter, $attr = array('dn'), $limit = null, $offset = null) {
		return $this->count($filter, $this->connection->ldapBaseGroups, $attr, $limit, $offset);
	}

	/**
	 * returns the number of available objects on the base DN
	 *
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return int|bool
	 */
	public function countObjects($limit = null, $offset = null) {
		return $this->count('objectclass=*', $this->connection->ldapBase, array('dn'), $limit, $offset);
	}

	/**
	 * retrieved. Results will according to the order in the array.
	 * @param int $limit optional, maximum results to be counted
	 * @param int $offset optional, a starting point
	 * @return array|false array with the search result as first value and pagedSearchOK as
	 * second | false if not successful
	 */
	private function executeSearch($filter, $base, &$attr = null, $limit = null, $offset = null) {
		if(!is_null($attr) && !is_array($attr)) {
			$attr = array(mb_strtolower($attr, 'UTF-8'));
		}

		// See if we have a resource, in case not cancel with message
		$cr = $this->connection->getConnectionResource();
		if(!$this->ldap->isResource($cr)) {
			// Seems like we didn't find any resource.
			// Return an empty array just like before.
			\OCP\Util::writeLog('user_ldap', 'Could not search, because resource is missing.', \OCP\Util::DEBUG);
			return false;
		}

		//check whether paged search should be attempted
		$pagedSearchOK = $this->initPagedSearch($filter, $base, $attr, intval($limit), $offset);

		$linkResources = array_pad(array(), count($base), $cr);
		$sr = $this->ldap->search($linkResources, $base, $filter, $attr);
		$error = $this->ldap->errno($cr);
		if(!is_array($sr) || $error !== 0) {
			\OCP\Util::writeLog('user_ldap',
				'Error when searching: '.$this->ldap->error($cr).
					' code '.$this->ldap->errno($cr),
				\OCP\Util::ERROR);
			\OCP\Util::writeLog('user_ldap', 'Attempt for Paging?  '.print_r($pagedSearchOK, true), \OCP\Util::ERROR);
			return false;
		}

		return array($sr, $pagedSearchOK);
	}

	/**
	 * processes an LDAP paged search operation
	 * @param array $sr the array containing the LDAP search resources
	 * @param string $filter the LDAP filter for the search
	 * @param array $base an array containing the LDAP subtree(s) that shall be searched
	 * @param int $iFoundItems number of results in the search operation
	 * @param int $limit maximum results to be counted
	 * @param int $offset a starting point
	 * @param bool $pagedSearchOK whether a paged search has been executed
	 * @param bool $skipHandling required for paged search when cookies to
	 * prior results need to be gained
	 * @return bool cookie validity, true if we have more pages, false otherwise.
	 */
	private function processPagedSearchStatus($sr, $filter, $base, $iFoundItems, $limit, $offset, $pagedSearchOK, $skipHandling) {
		$cookie = null;
		if($pagedSearchOK) {
			$cr = $this->connection->getConnectionResource();
			foreach($sr as $key => $res) {
				if($this->ldap->controlPagedResultResponse($cr, $res, $cookie)) {
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
			if($iFoundItems <= $limit) {
				$this->pagedSearchedSuccessful = true;
			}
		} else {
			if(!is_null($limit)) {
				\OCP\Util::writeLog('user_ldap', 'Paged search was not available', \OCP\Util::INFO);
			}
		}
		/* ++ Fixing RHDS searches with pages with zero results ++
		 * Return cookie status. If we don't have more pages, with RHDS
		 * cookie is null, with openldap cookie is an empty string and
		 * to 386ds '0' is a valid cookie. Even if $iFoundItems == 0
		 */
		return !empty($cookie) || $cookie === '0';
	}

	/**
	 * executes an LDAP search, but counts the results only
	 * @param string $filter the LDAP filter for the search
	 * @param array $base an array containing the LDAP subtree(s) that shall be searched
	 * @param string|string[] $attr optional, array, one or more attributes that shall be
	 * retrieved. Results will according to the order in the array.
	 * @param int $limit optional, maximum results to be counted
	 * @param int $offset optional, a starting point
	 * @param bool $skipHandling indicates whether the pages search operation is
	 * completed
	 * @return int|false Integer or false if the search could not be initialized
	 *
	 */
	private function count($filter, $base, $attr = null, $limit = null, $offset = null, $skipHandling = false) {
		\OCP\Util::writeLog('user_ldap', 'Count filter:  '.print_r($filter, true), \OCP\Util::DEBUG);

		$limitPerPage = intval($this->connection->ldapPagingSize);
		if(!is_null($limit) && $limit < $limitPerPage && $limit > 0) {
			$limitPerPage = $limit;
		}

		$counter = 0;
		$count = null;
		$this->connection->getConnectionResource();

		do {
			$search = $this->executeSearch($filter, $base, $attr,
										   $limitPerPage, $offset);
			if($search === false) {
				return $counter > 0 ? $counter : false;
			}
			list($sr, $pagedSearchOK) = $search;

			/* ++ Fixing RHDS searches with pages with zero results ++
			 * countEntriesInSearchResults() method signature changed
			 * by removing $limit and &$hasHitLimit parameters
			 */
			$count = $this->countEntriesInSearchResults($sr);
			$counter += $count;

			$hasMorePages = $this->processPagedSearchStatus($sr, $filter, $base, $count, $limitPerPage,
										$offset, $pagedSearchOK, $skipHandling);
			$offset += $limitPerPage;
			/* ++ Fixing RHDS searches with pages with zero results ++
			 * Continue now depends on $hasMorePages value
			 */
			$continue = $pagedSearchOK && $hasMorePages;
		} while($continue && (is_null($limit) || $limit <= 0 || $limit > $counter));

		return $counter;
	}

	/**
	 * @param array $searchResults
	 * @return int
	 */
	private function countEntriesInSearchResults($searchResults) {
		$cr = $this->connection->getConnectionResource();
		$counter = 0;

		foreach($searchResults as $res) {
			$count = intval($this->ldap->countEntries($cr, $res));
			$counter += $count;
		}

		return $counter;
	}

	/**
	 * Executes an LDAP search
	 * @param string $filter the LDAP filter for the search
	 * @param array $base an array containing the LDAP subtree(s) that shall be searched
	 * @param string|string[] $attr optional, array, one or more attributes that shall be
	 * @param int $limit
	 * @param int $offset
	 * @param bool $skipHandling
	 * @return array with the search result
	 */
	private function search($filter, $base, $attr = null, $limit = null, $offset = null, $skipHandling = false) {
		if($limit <= 0) {
			//otherwise search will fail
			$limit = null;
		}

		/* ++ Fixing RHDS searches with pages with zero results ++
		 * As we can have pages with zero results and/or pages with less
		 * than $limit results but with a still valid server 'cookie',
		 * loops through until we get $continue equals true and
		 * $findings['count'] < $limit
		 */
		$findings = array();
		$savedoffset = $offset;
		do {
			$continue = false;
			$search = $this->executeSearch($filter, $base, $attr, $limit, $offset);
			if($search === false) {
				return array();
			}
			list($sr, $pagedSearchOK) = $search;
			$cr = $this->connection->getConnectionResource();

			if($skipHandling) {
				//i.e. result do not need to be fetched, we just need the cookie
				//thus pass 1 or any other value as $iFoundItems because it is not
				//used
				$this->processPagedSearchStatus($sr, $filter, $base, 1, $limit,
								$offset, $pagedSearchOK,
								$skipHandling);
				return array();
			}

			foreach($sr as $res) {
				$findings = array_merge($findings, $this->ldap->getEntries($cr	, $res ));
			}

			$continue = $this->processPagedSearchStatus($sr, $filter, $base, $findings['count'],
								$limit, $offset, $pagedSearchOK,
										$skipHandling);
			$offset += $limit;
		} while ($continue && $pagedSearchOK && $findings['count'] < $limit);
		// reseting offset
		$offset = $savedoffset;

		// if we're here, probably no connection resource is returned.
		// to make ownCloud behave nicely, we simply give back an empty array.
		if(is_null($findings)) {
			return array();
		}

		if(!is_null($attr)) {
			$selection = array();
			$i = 0;
			foreach($findings as $item) {
				if(!is_array($item)) {
					continue;
				}
				$item = \OCP\Util::mb_array_change_key_case($item, MB_CASE_LOWER, 'UTF-8');
				foreach($attr as $key) {
					$key = mb_strtolower($key, 'UTF-8');
					if(isset($item[$key])) {
						if(is_array($item[$key]) && isset($item[$key]['count'])) {
							unset($item[$key]['count']);
						}
						if($key !== 'dn') {
							$selection[$i][$key] = $this->resemblesDN($key) ?
								$this->sanitizeDN($item[$key])
								: $item[$key];
						} else {
							$selection[$i][$key] = [$this->sanitizeDN($item[$key])];
						}
					}

				}
				$i++;
			}
			$findings = $selection;
		}
		//we slice the findings, when
		//a) paged search unsuccessful, though attempted
		//b) no paged search, but limit set
		if((!$this->getPagedSearchResultState()
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

	/**
	 * @param string $name
	 * @return bool|mixed|string
	 */
	public function sanitizeUsername($name) {
		if($this->connection->ldapIgnoreNamingRules) {
			return $name;
		}

		// Transliteration
		// latin characters to ASCII
		$name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);

		// Replacements
		$name = str_replace(' ', '_', $name);

		// Every remaining disallowed characters will be removed
		$name = preg_replace('/[^a-zA-Z0-9_.@-]/u', '', $name);

		return $name;
	}

	/**
	* escapes (user provided) parts for LDAP filter
	* @param string $input, the provided value
	* @param bool $allowAsterisk whether in * at the beginning should be preserved
	* @return string the escaped string
	*/
	public function escapeFilterPart($input, $allowAsterisk = false) {
		$asterisk = '';
		if($allowAsterisk && strlen($input) > 0 && $input[0] === '*') {
			$asterisk = '*';
			$input = mb_substr($input, 1, null, 'UTF-8');
		}
		$search  = array('*', '\\', '(', ')');
		$replace = array('\\*', '\\\\', '\\(', '\\)');
		return $asterisk . str_replace($search, $replace, $input);
	}

	/**
	 * combines the input filters with AND
	 * @param string[] $filters the filters to connect
	 * @return string the combined filter
	 */
	public function combineFilterWithAnd($filters) {
		return $this->combineFilter($filters, '&');
	}

	/**
	 * combines the input filters with OR
	 * @param string[] $filters the filters to connect
	 * @return string the combined filter
	 * Combines Filter arguments with OR
	 */
	public function combineFilterWithOr($filters) {
		return $this->combineFilter($filters, '|');
	}

	/**
	 * combines the input filters with given operator
	 * @param string[] $filters the filters to connect
	 * @param string $operator either & or |
	 * @return string the combined filter
	 */
	private function combineFilter($filters, $operator) {
		$combinedFilter = '('.$operator;
		foreach($filters as $filter) {
			if(!empty($filter) && $filter[0] !== '(') {
				$filter = '('.$filter.')';
			}
			$combinedFilter.=$filter;
		}
		$combinedFilter.=')';
		return $combinedFilter;
	}

	/**
	 * creates a filter part for to perform search for users
	 * @param string $search the search term
	 * @return string the final filter part to use in LDAP searches
	 */
	public function getFilterPartForUserSearch($search) {
		return $this->getFilterPartForSearch($search,
			$this->connection->ldapAttributesForUserSearch,
			$this->connection->ldapUserDisplayName);
	}

	/**
	 * creates a filter part for to perform search for groups
	 * @param string $search the search term
	 * @return string the final filter part to use in LDAP searches
	 */
	public function getFilterPartForGroupSearch($search) {
		return $this->getFilterPartForSearch($search,
			$this->connection->ldapAttributesForGroupSearch,
			$this->connection->ldapGroupDisplayName);
	}

	/**
	 * creates a filter part for searches by splitting up the given search
	 * string into single words
	 * @param string $search the search term
	 * @param string[] $searchAttributes needs to have at least two attributes,
	 * otherwise it does not make sense :)
	 * @return string the final filter part to use in LDAP searches
	 * @throws \Exception
	 */
	private function getAdvancedFilterPartForSearch($search, $searchAttributes) {
		if(!is_array($searchAttributes) || count($searchAttributes) < 2) {
			throw new \Exception('searchAttributes must be an array with at least two string');
		}
		$searchWords = explode(' ', trim($search));
		$wordFilters = array();
		foreach($searchWords as $word) {
			$word = $this->prepareSearchTerm($word);
			//every word needs to appear at least once
			$wordMatchOneAttrFilters = array();
			foreach($searchAttributes as $attr) {
				$wordMatchOneAttrFilters[] = $attr . '=' . $word;
			}
			$wordFilters[] = $this->combineFilterWithOr($wordMatchOneAttrFilters);
		}
		return $this->combineFilterWithAnd($wordFilters);
	}

	/**
	 * creates a filter part for searches
	 * @param string $search the search term
	 * @param string[]|null $searchAttributes
	 * @param string $fallbackAttribute a fallback attribute in case the user
	 * did not define search attributes. Typically the display name attribute.
	 * @return string the final filter part to use in LDAP searches
	 */
	private function getFilterPartForSearch($search, $searchAttributes, $fallbackAttribute) {
		$filter = array();
		$haveMultiSearchAttributes = (is_array($searchAttributes) && count($searchAttributes) > 0);
		if($haveMultiSearchAttributes && strpos(trim($search), ' ') !== false) {
			try {
				return $this->getAdvancedFilterPartForSearch($search, $searchAttributes);
			} catch(\Exception $e) {
				\OCP\Util::writeLog(
					'user_ldap',
					'Creating advanced filter for search failed, falling back to simple method.',
					\OCP\Util::INFO
				);
			}
		}

		$search = $this->prepareSearchTerm($search);
		if(!is_array($searchAttributes) || count($searchAttributes) === 0) {
			if(empty($fallbackAttribute)) {
				return '';
			}
			$filter[] = $fallbackAttribute . '=' . $search;
		} else {
			foreach($searchAttributes as $attribute) {
				$filter[] = $attribute . '=' . $search;
			}
		}
		if(count($filter) === 1) {
			return '('.$filter[0].')';
		}
		return $this->combineFilterWithOr($filter);
	}

	/**
	 * returns the search term depending on whether we are allowed
	 * list users found by ldap with the current input appended by
	 * a *
	 * @return string
	 */
	private function prepareSearchTerm($term) {
		$config = \OC::$server->getConfig();

		$allowEnum = $config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes');

		$result = empty($term) ? '*' :
			$allowEnum !== 'no' ? $term . '*' : $term;
		return $result;
	}

	/**
	 * returns the filter used for counting users
	 * @return string
	 */
	public function getFilterForUserCount() {
		$filter = $this->combineFilterWithAnd(array(
			$this->connection->ldapUserFilter,
			$this->connection->ldapUserDisplayName . '=*'
		));

		return $filter;
	}

	/**
	 * @param string $name
	 * @param string $password
	 * @return bool
	 */
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
	 * reverse lookup of a DN given a known UUID
	 *
	 * @param string $uuid
	 * @return string
	 * @throws \Exception
	 */
	public function getUserDnByUuid($uuid) {
		$uuidOverride = $this->connection->ldapExpertUUIDUserAttr;
		$filter       = $this->connection->ldapUserFilter;
		$base         = $this->connection->ldapBaseUsers;

		if($this->connection->ldapUuidUserAttribute === 'auto' && empty($uuidOverride)) {
			// Sacrebleu! The UUID attribute is unknown :( We need first an
			// existing DN to be able to reliably detect it.
			$result = $this->search($filter, $base, ['dn'], 1);
			if(!isset($result[0]) || !isset($result[0]['dn'])) {
				throw new \Exception('Cannot determine UUID attribute');
			}
			$dn = $result[0]['dn'][0];
			if(!$this->detectUuidAttribute($dn, true)) {
				throw new \Exception('Cannot determine UUID attribute');
			}
		} else {
			// The UUID attribute is either known or an override is given.
			// By calling this method we ensure that $this->connection->$uuidAttr
			// is definitely set
			if(!$this->detectUuidAttribute('', true)) {
				throw new \Exception('Cannot determine UUID attribute');
			}
		}

		$uuidAttr = $this->connection->ldapUuidUserAttribute;
		if($uuidAttr === 'guid' || $uuidAttr === 'objectguid') {
			$uuid = $this->formatGuid2ForFilterUser($uuid);
		}

		$filter = $uuidAttr . '=' . $uuid;
		$result = $this->searchUsers($filter, ['dn'], 2);
		if(is_array($result) && isset($result[0]) && isset($result[0]['dn']) && count($result) === 1) {
			// we put the count into account to make sure that this is
			// really unique
			return $result[0]['dn'][0];
		}

		throw new \Exception('Cannot determine UUID attribute');
	}

	/**
	 * auto-detects the directory's UUID attribute
	 * @param string $dn a known DN used to check against
	 * @param bool $isUser
	 * @param bool $force the detection should be run, even if it is not set to auto
	 * @return bool true on success, false otherwise
	 */
	private function detectUuidAttribute($dn, $isUser = true, $force = false) {
		if($isUser) {
			$uuidAttr     = 'ldapUuidUserAttribute';
			$uuidOverride = $this->connection->ldapExpertUUIDUserAttr;
		} else {
			$uuidAttr     = 'ldapUuidGroupAttribute';
			$uuidOverride = $this->connection->ldapExpertUUIDGroupAttr;
		}

		if(($this->connection->$uuidAttr !== 'auto') && !$force) {
			return true;
		}

		if(!empty($uuidOverride) && !$force) {
			$this->connection->$uuidAttr = $uuidOverride;
			return true;
		}

		// for now, supported attributes are entryUUID, nsuniqueid, objectGUID, ipaUniqueID
		$testAttributes = array('entryuuid', 'nsuniqueid', 'objectguid', 'guid', 'ipauniqueid');

		foreach($testAttributes as $attribute) {
			$value = $this->readAttribute($dn, $attribute);
			if(is_array($value) && isset($value[0]) && !empty($value[0])) {
				\OCP\Util::writeLog('user_ldap',
									'Setting '.$attribute.' as '.$uuidAttr,
									\OCP\Util::DEBUG);
				$this->connection->$uuidAttr = $attribute;
				return true;
			}
		}
		\OCP\Util::writeLog('user_ldap',
							'Could not autodetect the UUID attribute',
							\OCP\Util::ERROR);

		return false;
	}

	/**
	 * @param string $dn
	 * @param bool $isUser
	 * @return string|bool
	 */
	public function getUUID($dn, $isUser = true) {
		if($isUser) {
			$uuidAttr     = 'ldapUuidUserAttribute';
			$uuidOverride = $this->connection->ldapExpertUUIDUserAttr;
		} else {
			$uuidAttr     = 'ldapUuidGroupAttribute';
			$uuidOverride = $this->connection->ldapExpertUUIDGroupAttr;
		}

		$uuid = false;
		if($this->detectUuidAttribute($dn, $isUser)) {
			$uuid = $this->readAttribute($dn, $this->connection->$uuidAttr);
			if( !is_array($uuid)
				&& !empty($uuidOverride)
				&& $this->detectUuidAttribute($dn, $isUser, true)) {
					$uuid = $this->readAttribute($dn,
												 $this->connection->$uuidAttr);
			}
			if(is_array($uuid) && isset($uuid[0]) && !empty($uuid[0])) {
				$uuid = $uuid[0];
			}
		}

		return $uuid;
	}

	/**
	 * converts a binary ObjectGUID into a string representation
	 * @param string $oguid the ObjectGUID in it's binary form as retrieved from AD
	 * @return string
	 * @link http://www.php.net/manual/en/function.ldap-get-values-len.php#73198
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
	 * the first three blocks of the string-converted GUID happen to be in
	 * reverse order. In order to use it in a filter, this needs to be
	 * corrected. Furthermore the dashes need to be replaced and \\ preprended
	 * to every two hax figures.
	 *
	 * If an invalid string is passed, it will be returned without change.
	 *
	 * @param string $guid
	 * @return string
	 */
	public function formatGuid2ForFilterUser($guid) {
		if(!is_string($guid)) {
			throw new \InvalidArgumentException('String expected');
		}
		$blocks = explode('-', $guid);
		if(count($blocks) !== 5) {
			/*
			 * Why not throw an Exception instead? This method is a utility
			 * called only when trying to figure out whether a "missing" known
			 * LDAP user was or was not renamed on the LDAP server. And this
			 * even on the use case that a reverse lookup is needed (UUID known,
			 * not DN), i.e. when finding users (search dialog, users page,
			 * login, …) this will not be fired. This occurs only if shares from
			 * a users are supposed to be mounted who cannot be found. Throwing
			 * an exception here would kill the experience for a valid, acting
			 * user. Instead we write a log message.
			 */
			\OC::$server->getLogger()->info(
				'Passed string does not resemble a valid GUID. Known UUID ' .
				'({uuid}) probably does not match UUID configuration.',
				[ 'app' => 'user_ldap', 'uuid' => $guid ]
			);
			return $guid;
		}
		for($i=0; $i < 3; $i++) {
			$pairs = str_split($blocks[$i], 2);
			$pairs = array_reverse($pairs);
			$blocks[$i] = implode('', $pairs);
		}
		for($i=0; $i < 5; $i++) {
			$pairs = str_split($blocks[$i], 2);
			$blocks[$i] = '\\' . implode('\\', $pairs);
		}
		return implode('', $blocks);
	}

	/**
	 * gets a SID of the domain of the given dn
	 * @param string $dn
	 * @return string|bool
	 */
	public function getSID($dn) {
		$domainDN = $this->getDomainDNFromDN($dn);
		$cacheKey = 'getSID-'.$domainDN;
		$sid = $this->connection->getFromCache($cacheKey);
		if(!is_null($sid)) {
			return $sid;
		}

		$objectSid = $this->readAttribute($domainDN, 'objectsid');
		if(!is_array($objectSid) || empty($objectSid)) {
			$this->connection->writeToCache($cacheKey, false);
			return false;
		}
		$domainObjectSid = $this->convertSID2Str($objectSid[0]);
		$this->connection->writeToCache($cacheKey, $domainObjectSid);

		return $domainObjectSid;
	}

	/**
	 * converts a binary SID into a string representation
	 * @param string $sid
	 * @return string
	 */
	public function convertSID2Str($sid) {
		// The format of a SID binary string is as follows:
		// 1 byte for the revision level
		// 1 byte for the number n of variable sub-ids
		// 6 bytes for identifier authority value
		// n*4 bytes for n sub-ids
		//
		// Example: 010400000000000515000000a681e50e4d6c6c2bca32055f
		//  Legend: RRNNAAAAAAAAAAAA11111111222222223333333344444444
		$revision = ord($sid[0]);
		$numberSubID = ord($sid[1]);

		$subIdStart = 8; // 1 + 1 + 6
		$subIdLength = 4;
		if (strlen($sid) !== $subIdStart + $subIdLength * $numberSubID) {
			// Incorrect number of bytes present.
			return '';
		}

		// 6 bytes = 48 bits can be represented using floats without loss of
		// precision (see https://gist.github.com/bantu/886ac680b0aef5812f71)
		$iav = number_format(hexdec(bin2hex(substr($sid, 2, 6))), 0, '', '');

		$subIDs = array();
		for ($i = 0; $i < $numberSubID; $i++) {
			$subID = unpack('V', substr($sid, $subIdStart + $subIdLength * $i, $subIdLength));
			$subIDs[] = sprintf('%u', $subID[1]);
		}

		// Result for example above: S-1-5-21-249921958-728525901-1594176202
		return sprintf('S-%d-%s-%s', $revision, $iav, implode('-', $subIDs));
	}

	/**
	 * converts a stored DN so it can be used as base parameter for LDAP queries, internally we store them for usage in LDAP filters
	 * @param string $dn the DN
	 * @return string
	 */
	private function DNasBaseParameter($dn) {
		return str_ireplace('\\5c', '\\', $dn);
	}

	/**
	 * checks if the given DN is part of the given base DN(s)
	 * @param string $dn the DN
	 * @param string[] $bases array containing the allowed base DN or DNs
	 * @return bool
	 */
	public function isDNPartOfBase($dn, $bases) {
		$belongsToBase = false;
		$bases = $this->sanitizeDN($bases);

		foreach($bases as $base) {
			$belongsToBase = true;
			if(mb_strripos($dn, $base, 0, 'UTF-8') !== (mb_strlen($dn, 'UTF-8')-mb_strlen($base, 'UTF-8'))) {
				$belongsToBase = false;
			}
			if($belongsToBase) {
				break;
			}
		}
		return $belongsToBase;
	}

	/**
	 * resets a running Paged Search operation
	 */
	private function abandonPagedSearch() {
		if($this->connection->hasPagedResultSupport) {
			$cr = $this->connection->getConnectionResource();
			$this->ldap->controlPagedResult($cr, 0, false, $this->lastCookie);
			$this->getPagedSearchResultState();
			$this->lastCookie = '';
			$this->cookies = array();
		}
	}

	/**
	 * get a cookie for the next LDAP paged search
	 * @param string $base a string with the base DN for the search
	 * @param string $filter the search filter to identify the correct search
	 * @param int $limit the limit (or 'pageSize'), to identify the correct search well
	 * @param int $offset the offset for the new search to identify the correct search really good
	 * @return string containing the key or empty if none is cached
	 */
	private function getPagedResultCookie($base, $filter, $limit, $offset) {
		if($offset === 0) {
			return '';
		}
		$offset -= $limit;
		//we work with cache here
		$cacheKey = 'lc' . crc32($base) . '-' . crc32($filter) . '-' . intval($limit) . '-' . intval($offset);
		$cookie = '';
		if(isset($this->cookies[$cacheKey])) {
			$cookie = $this->cookies[$cacheKey];
			if(is_null($cookie)) {
				$cookie = '';
			}
		}
		return $cookie;
	}

	/**
	 * checks whether an LDAP paged search operation has more pages that can be
	 * retrieved, typically when offset and limit are provided.
	 *
	 * Be very careful to use it: the last cookie value, which is inspected, can
	 * be reset by other operations. Best, call it immediately after a search(),
	 * searchUsers() or searchGroups() call. count-methods are probably safe as
	 * well. Don't rely on it with any fetchList-method.
	 * @return bool
	 */
	public function hasMoreResults() {
		if(!$this->connection->hasPagedResultSupport) {
			return false;
		}

		if(empty($this->lastCookie) && $this->lastCookie !== '0') {
			// as in RFC 2696, when all results are returned, the cookie will
			// be empty.
			return false;
		}

		return true;
	}

	/**
	 * set a cookie for LDAP paged search run
	 * @param string $base a string with the base DN for the search
	 * @param string $filter the search filter to identify the correct search
	 * @param int $limit the limit (or 'pageSize'), to identify the correct search well
	 * @param int $offset the offset for the run search to identify the correct search really good
	 * @param string $cookie string containing the cookie returned by ldap_control_paged_result_response
	 * @return void
	 */
	private function setPagedResultCookie($base, $filter, $limit, $offset, $cookie) {
		// allow '0' for 389ds
		if(!empty($cookie) || $cookie === '0') {
			$cacheKey = 'lc' . crc32($base) . '-' . crc32($filter) . '-' .intval($limit) . '-' . intval($offset);
			$this->cookies[$cacheKey] = $cookie;
			$this->lastCookie = $cookie;
		}
	}

	/**
	 * Check whether the most recent paged search was successful. It flushed the state var. Use it always after a possible paged search.
	 * @return boolean|null true on success, null or false otherwise
	 */
	public function getPagedSearchResultState() {
		$result = $this->pagedSearchedSuccessful;
		$this->pagedSearchedSuccessful = null;
		return $result;
	}

	/**
	 * Prepares a paged search, if possible
	 * @param string $filter the LDAP filter for the search
	 * @param string[] $bases an array containing the LDAP subtree(s) that shall be searched
	 * @param string[] $attr optional, when a certain attribute shall be filtered outside
	 * @param int $limit
	 * @param int $offset
	 * @return bool|true
	 */
	private function initPagedSearch($filter, $bases, $attr, $limit, $offset) {
		$pagedSearchOK = false;
		if($this->connection->hasPagedResultSupport && ($limit !== 0)) {
			$offset = intval($offset); //can be null
			\OCP\Util::writeLog('user_ldap',
				'initializing paged search for  Filter '.$filter.' base '.print_r($bases, true)
				.' attr '.print_r($attr, true). ' limit ' .$limit.' offset '.$offset,
				\OCP\Util::DEBUG);
			//get the cookie from the search for the previous search, required by LDAP
			foreach($bases as $base) {

				$cookie = $this->getPagedResultCookie($base, $filter, $limit, $offset);
				if(empty($cookie) && $cookie !== "0" && ($offset > 0)) {
					// no cookie known, although the offset is not 0. Maybe cache run out. We need
					// to start all over *sigh* (btw, Dear Reader, did you know LDAP paged
					// searching was designed by MSFT?)
					// 		Lukas: No, but thanks to reading that source I finally know!
					// '0' is valid, because 389ds
					$reOffset = ($offset - $limit) < 0 ? 0 : $offset - $limit;
					//a bit recursive, $offset of 0 is the exit
					\OCP\Util::writeLog('user_ldap', 'Looking for cookie L/O '.$limit.'/'.$reOffset, \OCP\Util::INFO);
					$this->search($filter, array($base), $attr, $limit, $reOffset, true);
					$cookie = $this->getPagedResultCookie($base, $filter, $limit, $offset);
					//still no cookie? obviously, the server does not like us. Let's skip paging efforts.
					//TODO: remember this, probably does not change in the next request...
					if(empty($cookie) && $cookie !== '0') {
						// '0' is valid, because 389ds
						$cookie = null;
					}
				}
				if(!is_null($cookie)) {
					//since offset = 0, this is a new search. We abandon other searches that might be ongoing.
					$this->abandonPagedSearch();
					$pagedSearchOK = $this->ldap->controlPagedResult(
						$this->connection->getConnectionResource(), $limit,
						false, $cookie);
					if(!$pagedSearchOK) {
						return false;
					}
					\OCP\Util::writeLog('user_ldap', 'Ready for a paged search', \OCP\Util::DEBUG);
				} else {
					\OCP\Util::writeLog('user_ldap',
						'No paged search for us, Cpt., Limit '.$limit.' Offset '.$offset,
						\OCP\Util::INFO);
				}

			}
		/* ++ Fixing RHDS searches with pages with zero results ++
		 * We coudn't get paged searches working with our RHDS for login ($limit = 0),
		 * due to pages with zero results.
		 * So we added "&& !empty($this->lastCookie)" to this test to ignore pagination
		 * if we don't have a previous paged search.
		 */
		} else if($this->connection->hasPagedResultSupport && $limit === 0 && !empty($this->lastCookie)) {
			// a search without limit was requested. However, if we do use
			// Paged Search once, we always must do it. This requires us to
			// initialize it with the configured page size.
			$this->abandonPagedSearch();
			// in case someone set it to 0 … use 500, otherwise no results will
			// be returned.
			$pageSize = intval($this->connection->ldapPagingSize) > 0 ? intval($this->connection->ldapPagingSize) : 500;
			$pagedSearchOK = $this->ldap->controlPagedResult(
				$this->connection->getConnectionResource(), $pageSize, false, ''
			);
		}

		return $pagedSearchOK;
	}

}
