<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Alexander Bergolth <leo@strike.wu.ac.at>
 * @author Alex Weirig <alex.weirig@technolink.lu>
 * @author alexweirig <alex.weirig@technolink.lu>
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Frédéric Fortier <frederic.fortier@oronospolytechnique.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Nicolas Grekas <nicolas.grekas@gmail.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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

use OC\Cache\CappedMemoryCache;

class Group_LDAP extends BackendUtility implements \OCP\GroupInterface {
	protected $enabled = false;

	/**
	 * @var string[] $cachedGroupMembers array of users with gid as key
	 */
	protected $cachedGroupMembers;

	/**
	 * @var string[] $cachedGroupsByMember array of groups with uid as key
	 */
	protected $cachedGroupsByMember;

	public function __construct(Access $access) {
		parent::__construct($access);
		$filter = $this->access->connection->ldapGroupFilter;
		$gassoc = $this->access->connection->ldapGroupMemberAssocAttr;
		if(!empty($filter) && !empty($gassoc)) {
			$this->enabled = true;
		}

		$this->cachedGroupMembers = new CappedMemoryCache();
		$this->cachedGroupsByMember = new CappedMemoryCache();
	}

	/**
	 * is user in group?
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 * @return bool
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public function inGroup($uid, $gid) {
		if(!$this->enabled) {
			return false;
		}
		$cacheKey = 'inGroup'.$uid.':'.$gid;
		$inGroup = $this->access->connection->getFromCache($cacheKey);
		if(!is_null($inGroup)) {
			return (bool)$inGroup;
		}

		$userDN = $this->access->username2dn($uid);

		if(isset($this->cachedGroupMembers[$gid])) {
			$isInGroup = in_array($userDN, $this->cachedGroupMembers[$gid]);
			return $isInGroup;
		}

		$cacheKeyMembers = 'inGroup-members:'.$gid;
		$members = $this->access->connection->getFromCache($cacheKeyMembers);
		if(!is_null($members)) {
			$this->cachedGroupMembers[$gid] = $members;
			$isInGroup = in_array($userDN, $members);
			$this->access->connection->writeToCache($cacheKey, $isInGroup);
			return $isInGroup;
		}

		$groupDN = $this->access->groupname2dn($gid);
		// just in case
		if(!$groupDN || !$userDN) {
			$this->access->connection->writeToCache($cacheKey, false);
			return false;
		}

		//check primary group first
		if($gid === $this->getUserPrimaryGroup($userDN)) {
			$this->access->connection->writeToCache($cacheKey, true);
			return true;
		}

		//usually, LDAP attributes are said to be case insensitive. But there are exceptions of course.
		$members = $this->_groupMembers($groupDN);
		$members = array_keys($members); // uids are returned as keys
		if(!is_array($members) || count($members) === 0) {
			$this->access->connection->writeToCache($cacheKey, false);
			return false;
		}

		//extra work if we don't get back user DNs
		if(strtolower($this->access->connection->ldapGroupMemberAssocAttr) === 'memberuid') {
			$dns = array();
			$filterParts = array();
			$bytes = 0;
			foreach($members as $mid) {
				$filter = str_replace('%uid', $mid, $this->access->connection->ldapLoginFilter);
				$filterParts[] = $filter;
				$bytes += strlen($filter);
				if($bytes >= 9000000) {
					// AD has a default input buffer of 10 MB, we do not want
					// to take even the chance to exceed it
					$filter = $this->access->combineFilterWithOr($filterParts);
					$bytes = 0;
					$filterParts = array();
					$users = $this->access->fetchListOfUsers($filter, 'dn', count($filterParts));
					$dns = array_merge($dns, $users);
				}
			}
			if(count($filterParts) > 0) {
				$filter = $this->access->combineFilterWithOr($filterParts);
				$users = $this->access->fetchListOfUsers($filter, 'dn', count($filterParts));
				$dns = array_merge($dns, $users);
			}
			$members = $dns;
		}

		$isInGroup = in_array($userDN, $members);
		$this->access->connection->writeToCache($cacheKey, $isInGroup);
		$this->access->connection->writeToCache($cacheKeyMembers, $members);
		$this->cachedGroupMembers[$gid] = $members;

		return $isInGroup;
	}

	/**
	 * @param string $dnGroup
	 * @return array
	 *
	 * For a group that has user membership defined by an LDAP search url attribute returns the users
	 * that match the search url otherwise returns an empty array.
	 */
	public function getDynamicGroupMembers($dnGroup) {
		$dynamicGroupMemberURL = strtolower($this->access->connection->ldapDynamicGroupMemberURL);

		if (empty($dynamicGroupMemberURL)) {
			return array();
		}

		$dynamicMembers = array();
		$memberURLs = $this->access->readAttribute(
			$dnGroup,
			$dynamicGroupMemberURL,
			$this->access->connection->ldapGroupFilter
		);
		if ($memberURLs !== false) {
			// this group has the 'memberURL' attribute so this is a dynamic group
			// example 1: ldap:///cn=users,cn=accounts,dc=dcsubbase,dc=dcbase??one?(o=HeadOffice)
			// example 2: ldap:///cn=users,cn=accounts,dc=dcsubbase,dc=dcbase??one?(&(o=HeadOffice)(uidNumber>=500))
			$pos = strpos($memberURLs[0], '(');
			if ($pos !== false) {
				$memberUrlFilter = substr($memberURLs[0], $pos);
				$foundMembers = $this->access->searchUsers($memberUrlFilter,'dn');
				$dynamicMembers = array();
				foreach($foundMembers as $value) {
					$dynamicMembers[$value['dn'][0]] = 1;
				}
			} else {
				\OCP\Util::writeLog('user_ldap', 'No search filter found on member url '.
					'of group ' . $dnGroup, \OCP\Util::DEBUG);
			}
		}
		return $dynamicMembers;
	}

	/**
	 * @param string $dnGroup
	 * @param array|null &$seen
	 * @return array|mixed|null
	 */
	private function _groupMembers($dnGroup, &$seen = null) {
		if ($seen === null) {
			$seen = array();
		}
		$allMembers = array();
		if (array_key_exists($dnGroup, $seen)) {
			// avoid loops
			return array();
		}
		// used extensively in cron job, caching makes sense for nested groups
		$cacheKey = '_groupMembers'.$dnGroup;
		$groupMembers = $this->access->connection->getFromCache($cacheKey);
		if(!is_null($groupMembers)) {
			return $groupMembers;
		}
		$seen[$dnGroup] = 1;
		$members = $this->access->readAttribute($dnGroup, $this->access->connection->ldapGroupMemberAssocAttr,
												$this->access->connection->ldapGroupFilter);
		if (is_array($members)) {
			foreach ($members as $memberDN) {
				$allMembers[$memberDN] = 1;
				$nestedGroups = $this->access->connection->ldapNestedGroups;
				if (!empty($nestedGroups)) {
					$subMembers = $this->_groupMembers($memberDN, $seen);
					if ($subMembers) {
						$allMembers = array_merge($allMembers, $subMembers);
					}
				}
			}
		}
		
		$allMembers = array_merge($allMembers, $this->getDynamicGroupMembers($dnGroup));
		
		$this->access->connection->writeToCache($cacheKey, $allMembers);
		return $allMembers;
	}

	/**
	 * @param string $DN
	 * @param array|null &$seen
	 * @return array
	 */
	private function _getGroupDNsFromMemberOf($DN, &$seen = null) {
		if ($seen === null) {
			$seen = array();
		}
		if (array_key_exists($DN, $seen)) {
			// avoid loops
			return array();
		}
		$seen[$DN] = 1;
		$groups = $this->access->readAttribute($DN, 'memberOf');
		if (!is_array($groups)) {
			return array();
		}
		$groups = $this->access->groupsMatchFilter($groups);
		$allGroups =  $groups;
		$nestedGroups = $this->access->connection->ldapNestedGroups;
		if (intval($nestedGroups) === 1) {
			foreach ($groups as $group) {
				$subGroups = $this->_getGroupDNsFromMemberOf($group, $seen);
				$allGroups = array_merge($allGroups, $subGroups);
			}
		}
		return $allGroups;	
	}

	/**
	 * translates a primary group ID into an ownCloud internal name
	 * @param string $gid as given by primaryGroupID on AD
	 * @param string $dn a DN that belongs to the same domain as the group
	 * @return string|bool
	 */
	public function primaryGroupID2Name($gid, $dn) {
		$cacheKey = 'primaryGroupIDtoName';
		$groupNames = $this->access->connection->getFromCache($cacheKey);
		if(!is_null($groupNames) && isset($groupNames[$gid])) {
			return $groupNames[$gid];
		}

		$domainObjectSid = $this->access->getSID($dn);
		if($domainObjectSid === false) {
			return false;
		}

		//we need to get the DN from LDAP
		$filter = $this->access->combineFilterWithAnd(array(
			$this->access->connection->ldapGroupFilter,
			'objectsid=' . $domainObjectSid . '-' . $gid
		));
		$result = $this->access->searchGroups($filter, array('dn'), 1);
		if(empty($result)) {
			return false;
		}
		$dn = $result[0]['dn'][0];

		//and now the group name
		//NOTE once we have separate ownCloud group IDs and group names we can
		//directly read the display name attribute instead of the DN
		$name = $this->access->dn2groupname($dn);

		$this->access->connection->writeToCache($cacheKey, $name);

		return $name;
	}

	/**
	 * returns the entry's primary group ID
	 * @param string $dn
	 * @param string $attribute
	 * @return string|bool
	 */
	private function getEntryGroupID($dn, $attribute) {
		$value = $this->access->readAttribute($dn, $attribute);
		if(is_array($value) && !empty($value)) {
			return $value[0];
		}
		return false;
	}

	/**
	 * returns the group's primary ID
	 * @param string $dn
	 * @return string|bool
	 */
	public function getGroupPrimaryGroupID($dn) {
		return $this->getEntryGroupID($dn, 'primaryGroupToken');
	}

	/**
	 * returns the user's primary group ID
	 * @param string $dn
	 * @return string|bool
	 */
	public function getUserPrimaryGroupIDs($dn) {
		$primaryGroupID = false;
		if($this->access->connection->hasPrimaryGroups) {
			$primaryGroupID = $this->getEntryGroupID($dn, 'primaryGroupID');
			if($primaryGroupID === false) {
				$this->access->connection->hasPrimaryGroups = false;
			}
		}
		return $primaryGroupID;
	}

	/**
	 * returns a filter for a "users in primary group" search or count operation
	 *
	 * @param string $groupDN
	 * @param string $search
	 * @return string
	 * @throws \Exception
	 */
	private function prepareFilterForUsersInPrimaryGroup($groupDN, $search = '') {
		$groupID = $this->getGroupPrimaryGroupID($groupDN);
		if($groupID === false) {
			throw new \Exception('Not a valid group');
		}

		$filterParts = [];
		$filterParts[] = $this->access->getFilterForUserCount();
		if(!empty($search)) {
			$filterParts[] = $this->access->getFilterPartForUserSearch($search);
		}
		$filterParts[] = 'primaryGroupID=' . $groupID;

		$filter = $this->access->combineFilterWithAnd($filterParts);

		return $filter;
	}

	/**
	 * returns a list of users that have the given group as primary group
	 *
	 * @param string $groupDN
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return string[]
	 */
	public function getUsersInPrimaryGroup($groupDN, $search = '', $limit = -1, $offset = 0) {
		try {
			$filter = $this->prepareFilterForUsersInPrimaryGroup($groupDN, $search);
			$users = $this->access->fetchListOfUsers(
				$filter,
				array($this->access->connection->ldapUserDisplayName, 'dn'),
				$limit,
				$offset
			);
			return $this->access->ownCloudUserNames($users);
		} catch (\Exception $e) {
			return array();
		}
	}

	/**
	 * returns the number of users that have the given group as primary group
	 *
	 * @param string $groupDN
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return int
	 */
	public function countUsersInPrimaryGroup($groupDN, $search = '', $limit = -1, $offset = 0) {
		try {
			$filter = $this->prepareFilterForUsersInPrimaryGroup($groupDN, $search);
			$users = $this->access->countUsers($filter, array('dn'), $limit, $offset);
			return (int)$users;
		} catch (\Exception $e) {
			return 0;
		}
	}

	/**
	 * gets the primary group of a user
	 * @param string $dn
	 * @return string
	 */
	public function getUserPrimaryGroup($dn) {
		$groupID = $this->getUserPrimaryGroupIDs($dn);
		if($groupID !== false) {
			$groupName = $this->primaryGroupID2Name($groupID, $dn);
			if($groupName !== false) {
				return $groupName;
			}
		}

		return false;
	}

	/**
	 * Get all groups a user belongs to
	 * @param string $uid Name of the user
	 * @return array with group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 *
	 * This function includes groups based on dynamic group membership.
	 */
	public function getUserGroups($uid) {
		if(!$this->enabled) {
			return array();
		}
		$cacheKey = 'getUserGroups'.$uid;
		$userGroups = $this->access->connection->getFromCache($cacheKey);
		if(!is_null($userGroups)) {
			return $userGroups;
		}
		$userDN = $this->access->username2dn($uid);
		if(!$userDN) {
			$this->access->connection->writeToCache($cacheKey, array());
			return array();
		}

		$groups = [];
		$primaryGroup = $this->getUserPrimaryGroup($userDN);

		$dynamicGroupMemberURL = strtolower($this->access->connection->ldapDynamicGroupMemberURL);

		if (!empty($dynamicGroupMemberURL)) {
			// look through dynamic groups to add them to the result array if needed
			$groupsToMatch = $this->access->fetchListOfGroups(
				$this->access->connection->ldapGroupFilter,array('dn',$dynamicGroupMemberURL));
			foreach($groupsToMatch as $dynamicGroup) {
				if (!array_key_exists($dynamicGroupMemberURL, $dynamicGroup)) {
					continue;
				}
				$pos = strpos($dynamicGroup[$dynamicGroupMemberURL][0], '(');
				if ($pos !== false) {
					$memberUrlFilter = substr($dynamicGroup[$dynamicGroupMemberURL][0],$pos);
					// apply filter via ldap search to see if this user is in this
					// dynamic group
					$userMatch = $this->access->readAttribute(
						$userDN,
						$this->access->connection->ldapUserDisplayName,
						$memberUrlFilter
					);
					if ($userMatch !== false) {
						// match found so this user is in this group
						$groupName = $this->access->dn2groupname($dynamicGroup['dn'][0]);
						if(is_string($groupName)) {
							// be sure to never return false if the dn could not be
							// resolved to a name, for whatever reason.
							$groups[] = $groupName;
						}
					}
				} else {
					\OCP\Util::writeLog('user_ldap', 'No search filter found on member url '.
						'of group ' . print_r($dynamicGroup, true), \OCP\Util::DEBUG);
				}
			}
		}

		// if possible, read out membership via memberOf. It's far faster than
		// performing a search, which still is a fallback later.
		if(intval($this->access->connection->hasMemberOfFilterSupport) === 1
			&& intval($this->access->connection->useMemberOfToDetectMembership) === 1
		) {
			$groupDNs = $this->_getGroupDNsFromMemberOf($userDN);
			if (is_array($groupDNs)) {
				foreach ($groupDNs as $dn) {
					$groupName = $this->access->dn2groupname($dn);
					if(is_string($groupName)) {
						// be sure to never return false if the dn could not be
						// resolved to a name, for whatever reason.
						$groups[] = $groupName;
					}
				}
			}
			
			if($primaryGroup !== false) {
				$groups[] = $primaryGroup;
			}
			$this->access->connection->writeToCache($cacheKey, $groups);
			return $groups;
		}

		//uniqueMember takes DN, memberuid the uid, so we need to distinguish
		if((strtolower($this->access->connection->ldapGroupMemberAssocAttr) === 'uniquemember')
			|| (strtolower($this->access->connection->ldapGroupMemberAssocAttr) === 'member')
		) {
			$uid = $userDN;
		} else if(strtolower($this->access->connection->ldapGroupMemberAssocAttr) === 'memberuid') {
			$result = $this->access->readAttribute($userDN, 'uid');
			if ($result === false) {
				\OCP\Util::writeLog('user_ldap', 'No uid attribute found for DN ' . $userDN . ' on '.
					$this->access->connection->ldapHost, \OCP\Util::DEBUG);
			}
			$uid = $result[0];
		} else {
			// just in case
			$uid = $userDN;
		}

		if(isset($this->cachedGroupsByMember[$uid])) {
			$groups = array_merge($groups, $this->cachedGroupsByMember[$uid]);
		} else {
			$groupsByMember = array_values($this->getGroupsByMember($uid));
			$groupsByMember = $this->access->ownCloudGroupNames($groupsByMember);
			$this->cachedGroupsByMember[$uid] = $groupsByMember;
			$groups = array_merge($groups, $groupsByMember);
		}

		if($primaryGroup !== false) {
			$groups[] = $primaryGroup;
		}

		$groups = array_unique($groups, SORT_LOCALE_STRING);
		$this->access->connection->writeToCache($cacheKey, $groups);

		return $groups;
	}

	/**
	 * @param string $dn
	 * @param array|null &$seen
	 * @return array
	 */
	private function getGroupsByMember($dn, &$seen = null) {
		if ($seen === null) {
			$seen = array();
		}
		$allGroups = array();
		if (array_key_exists($dn, $seen)) {
			// avoid loops
			return array();
		}
		$seen[$dn] = true;
		$filter = $this->access->combineFilterWithAnd(array(
			$this->access->connection->ldapGroupFilter,
			$this->access->connection->ldapGroupMemberAssocAttr.'='.$dn
		));
		$groups = $this->access->fetchListOfGroups($filter,
			array($this->access->connection->ldapGroupDisplayName, 'dn'));
		if (is_array($groups)) {
			foreach ($groups as $groupobj) {
				$groupDN = $groupobj['dn'][0];
				$allGroups[$groupDN] = $groupobj;
				$nestedGroups = $this->access->connection->ldapNestedGroups;
				if (!empty($nestedGroups)) {
					$supergroups = $this->getGroupsByMember($groupDN, $seen);
					if (is_array($supergroups) && (count($supergroups)>0)) {
						$allGroups = array_merge($allGroups, $supergroups);
					}
				}
			}
		}
		return $allGroups;
	}

	/**
	 * get a list of all users in a group
	 *
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array with user ids
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		if(!$this->enabled) {
			return array();
		}
		if(!$this->groupExists($gid)) {
			return array();
		}
		$search = $this->access->escapeFilterPart($search, true);
		$cacheKey = 'usersInGroup-'.$gid.'-'.$search.'-'.$limit.'-'.$offset;
		// check for cache of the exact query
		$groupUsers = $this->access->connection->getFromCache($cacheKey);
		if(!is_null($groupUsers)) {
			return $groupUsers;
		}

		// check for cache of the query without limit and offset
		$groupUsers = $this->access->connection->getFromCache('usersInGroup-'.$gid.'-'.$search);
		if(!is_null($groupUsers)) {
			$groupUsers = array_slice($groupUsers, $offset, $limit);
			$this->access->connection->writeToCache($cacheKey, $groupUsers);
			return $groupUsers;
		}

		if($limit === -1) {
			$limit = null;
		}
		$groupDN = $this->access->groupname2dn($gid);
		if(!$groupDN) {
			// group couldn't be found, return empty resultset
			$this->access->connection->writeToCache($cacheKey, array());
			return array();
		}

		$primaryUsers = $this->getUsersInPrimaryGroup($groupDN, $search, $limit, $offset);
		$members = array_keys($this->_groupMembers($groupDN));
		if(!$members && empty($primaryUsers)) {
			//in case users could not be retrieved, return empty result set
			$this->access->connection->writeToCache($cacheKey, array());
			return array();
		}

		$groupUsers = array();
		$isMemberUid = (strtolower($this->access->connection->ldapGroupMemberAssocAttr) === 'memberuid');
		$attrs = $this->access->userManager->getAttributes(true);
		foreach($members as $member) {
			if($isMemberUid) {
				//we got uids, need to get their DNs to 'translate' them to user names
				$filter = $this->access->combineFilterWithAnd(array(
					str_replace('%uid', $member, $this->access->connection->ldapLoginFilter),
					$this->access->getFilterPartForUserSearch($search)
				));
				$ldap_users = $this->access->fetchListOfUsers($filter, $attrs, 1);
				if(count($ldap_users) < 1) {
					continue;
				}
				$groupUsers[] = $this->access->dn2username($ldap_users[0]['dn'][0]);
			} else {
				//we got DNs, check if we need to filter by search or we can give back all of them
				if(!empty($search)) {
					if(!$this->access->readAttribute($member,
						$this->access->connection->ldapUserDisplayName,
						$this->access->getFilterPartForUserSearch($search))) {
						continue;
					}
				}
				// dn2username will also check if the users belong to the allowed base
				if($ocname = $this->access->dn2username($member)) {
					$groupUsers[] = $ocname;
				}
			}
		}

		$groupUsers = array_unique(array_merge($groupUsers, $primaryUsers));
		natsort($groupUsers);
		$this->access->connection->writeToCache('usersInGroup-'.$gid.'-'.$search, $groupUsers);
		$groupUsers = array_slice($groupUsers, $offset, $limit);


		$this->access->connection->writeToCache($cacheKey, $groupUsers);

		return $groupUsers;
	}

	/**
	 * returns the number of users in a group, who match the search term
	 * @param string $gid the internal group name
	 * @param string $search optional, a search string
	 * @return int|bool
	 */
	public function countUsersInGroup($gid, $search = '') {
		$cacheKey = 'countUsersInGroup-'.$gid.'-'.$search;
		if(!$this->enabled || !$this->groupExists($gid)) {
			return false;
		}
		$groupUsers = $this->access->connection->getFromCache($cacheKey);
		if(!is_null($groupUsers)) {
			return $groupUsers;
		}

		$groupDN = $this->access->groupname2dn($gid);
		if(!$groupDN) {
			// group couldn't be found, return empty result set
			$this->access->connection->writeToCache($cacheKey, false);
			return false;
		}

		$members = array_keys($this->_groupMembers($groupDN));
		$primaryUserCount = $this->countUsersInPrimaryGroup($groupDN, '');
		if(!$members && $primaryUserCount === 0) {
			//in case users could not be retrieved, return empty result set
			$this->access->connection->writeToCache($cacheKey, false);
			return false;
		}

		if(empty($search)) {
			$groupUsers = count($members) + $primaryUserCount;
			$this->access->connection->writeToCache($cacheKey, $groupUsers);
			return $groupUsers;
		}
		$search = $this->access->escapeFilterPart($search, true);
		$isMemberUid =
			(strtolower($this->access->connection->ldapGroupMemberAssocAttr)
			=== 'memberuid');

		//we need to apply the search filter
		//alternatives that need to be checked:
		//a) get all users by search filter and array_intersect them
		//b) a, but only when less than 1k 10k ?k users like it is
		//c) put all DNs|uids in a LDAP filter, combine with the search string
		//   and let it count.
		//For now this is not important, because the only use of this method
		//does not supply a search string
		$groupUsers = array();
		foreach($members as $member) {
			if($isMemberUid) {
				//we got uids, need to get their DNs to 'translate' them to user names
				$filter = $this->access->combineFilterWithAnd(array(
					str_replace('%uid', $member, $this->access->connection->ldapLoginFilter),
					$this->access->getFilterPartForUserSearch($search)
				));
				$ldap_users = $this->access->fetchListOfUsers($filter, 'dn', 1);
				if(count($ldap_users) < 1) {
					continue;
				}
				$groupUsers[] = $this->access->dn2username($ldap_users[0]);
			} else {
				//we need to apply the search filter now
				if(!$this->access->readAttribute($member,
					$this->access->connection->ldapUserDisplayName,
					$this->access->getFilterPartForUserSearch($search))) {
					continue;
				}
				// dn2username will also check if the users belong to the allowed base
				if($ocname = $this->access->dn2username($member)) {
					$groupUsers[] = $ocname;
				}
			}
		}

		//and get users that have the group as primary
		$primaryUsers = $this->countUsersInPrimaryGroup($groupDN, $search);

		return count($groupUsers) + $primaryUsers;
	}

	/**
	 * get a list of all groups
	 *
	 * @param string $search
	 * @param $limit
	 * @param int $offset
	 * @return array with group names
	 *
	 * Returns a list with all groups (used by getGroups)
	 */
	protected function getGroupsChunk($search = '', $limit = -1, $offset = 0) {
		if(!$this->enabled) {
			return array();
		}
		$cacheKey = 'getGroups-'.$search.'-'.$limit.'-'.$offset;

		//Check cache before driving unnecessary searches
		\OCP\Util::writeLog('user_ldap', 'getGroups '.$cacheKey, \OCP\Util::DEBUG);
		$ldap_groups = $this->access->connection->getFromCache($cacheKey);
		if(!is_null($ldap_groups)) {
			return $ldap_groups;
		}

		// if we'd pass -1 to LDAP search, we'd end up in a Protocol
		// error. With a limit of 0, we get 0 results. So we pass null.
		if($limit <= 0) {
			$limit = null;
		}
		$filter = $this->access->combineFilterWithAnd(array(
			$this->access->connection->ldapGroupFilter,
			$this->access->getFilterPartForGroupSearch($search)
		));
		\OCP\Util::writeLog('user_ldap', 'getGroups Filter '.$filter, \OCP\Util::DEBUG);
		$ldap_groups = $this->access->fetchListOfGroups($filter,
				array($this->access->connection->ldapGroupDisplayName, 'dn'),
				$limit,
				$offset);
		$ldap_groups = $this->access->ownCloudGroupNames($ldap_groups);

		$this->access->connection->writeToCache($cacheKey, $ldap_groups);
		return $ldap_groups;
	}

	/**
	 * get a list of all groups using a paged search
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array with group names
	 *
	 * Returns a list with all groups
	 * Uses a paged search if available to override a
	 * server side search limit.
	 * (active directory has a limit of 1000 by default)
	 */
	public function getGroups($search = '', $limit = -1, $offset = 0) {
		if(!$this->enabled) {
			return array();
		}
		$search = $this->access->escapeFilterPart($search, true);
		$pagingSize = $this->access->connection->ldapPagingSize;
		if ((! $this->access->connection->hasPagedResultSupport)
		   	|| empty($pagingSize)) {
			return $this->getGroupsChunk($search, $limit, $offset);
		}
		$maxGroups = 100000; // limit max results (just for safety reasons)
		if ($limit > -1) {
		   $overallLimit = min($limit + $offset, $maxGroups);
		} else {
		   $overallLimit = $maxGroups;
		}
		$chunkOffset = $offset;
		$allGroups = array();
		while ($chunkOffset < $overallLimit) {
			$chunkLimit = min($pagingSize, $overallLimit - $chunkOffset);
			$ldapGroups = $this->getGroupsChunk($search, $chunkLimit, $chunkOffset);
			$nread = count($ldapGroups);
			\OCP\Util::writeLog('user_ldap', 'getGroups('.$search.'): read '.$nread.' at offset '.$chunkOffset.' (limit: '.$chunkLimit.')', \OCP\Util::DEBUG);
			if ($nread) {
				$allGroups = array_merge($allGroups, $ldapGroups);
				$chunkOffset += $nread;
			}
			if ($nread < $chunkLimit) {
				break;
			}
		}
		return $allGroups;
	}

	/**
	 * @param string $group
	 * @return bool
	 */
	public function groupMatchesFilter($group) {
		return (strripos($group, $this->groupSearch) !== false);
	}

	/**
	 * check if a group exists
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid) {
		$groupExists = $this->access->connection->getFromCache('groupExists'.$gid);
		if(!is_null($groupExists)) {
			return (bool)$groupExists;
		}

		//getting dn, if false the group does not exist. If dn, it may be mapped
		//only, requires more checking.
		$dn = $this->access->groupname2dn($gid);
		if(!$dn) {
			$this->access->connection->writeToCache('groupExists'.$gid, false);
			return false;
		}

		//if group really still exists, we will be able to read its objectclass
		if(!is_array($this->access->readAttribute($dn, ''))) {
			$this->access->connection->writeToCache('groupExists'.$gid, false);
			return false;
		}

		$this->access->connection->writeToCache('groupExists'.$gid, true);
		return true;
	}

	/**
	* Check if backend implements actions
	* @param int $actions bitwise-or'ed actions
	* @return boolean
	*
	* Returns the supported actions as int to be
	* compared with OC_USER_BACKEND_CREATE_USER etc.
	*/
	public function implementsActions($actions) {
		return (bool)(\OC\Group\Backend::COUNT_USERS & $actions);
	}
}
