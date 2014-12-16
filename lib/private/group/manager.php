<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Group;

use OC\Hooks\PublicEmitter;
use OCP\IGroupManager;

/**
 * Class Manager
 *
 * Hooks available in scope \OC\Group:
 * - preAddUser(\OC\Group\Group $group, \OC\User\User $user)
 * - postAddUser(\OC\Group\Group $group, \OC\User\User $user)
 * - preRemoveUser(\OC\Group\Group $group, \OC\User\User $user)
 * - postRemoveUser(\OC\Group\Group $group, \OC\User\User $user)
 * - preDelete(\OC\Group\Group $group)
 * - postDelete(\OC\Group\Group $group)
 * - preCreate(string $groupId)
 * - postCreate(\OC\Group\Group $group)
 *
 * @package OC\Group
 */
class Manager extends PublicEmitter implements IGroupManager {
	/**
	 * @var \OC_Group_Backend[]|\OC_Group_Database[] $backends
	 */
	private $backends = array();

	/**
	 * @var \OC\User\Manager $userManager
	 */
	private $userManager;

	/**
	 * @var \OC\Group\Group[]
	 */
	private $cachedGroups = array();

	/**
	 * @var \OC\Group\Group[]
	 */
	private $cachedUserGroups = array();


	/**
	 * @param \OC\User\Manager $userManager
	 */
	public function __construct($userManager) {
		$this->userManager = $userManager;
		$cachedGroups = & $this->cachedGroups;
		$cachedUserGroups = & $this->cachedUserGroups;
		$this->listen('\OC\Group', 'postDelete', function ($group) use (&$cachedGroups, &$cachedUserGroups) {
			/**
			 * @var \OC\Group\Group $group
			 */
			unset($cachedGroups[$group->getGID()]);
			$cachedUserGroups = array();
		});
		$this->listen('\OC\Group', 'postAddUser', function ($group) use (&$cachedUserGroups) {
			/**
			 * @var \OC\Group\Group $group
			 */
			$cachedUserGroups = array();
		});
		$this->listen('\OC\Group', 'postRemoveUser', function ($group) use (&$cachedUserGroups) {
			/**
			 * @var \OC\Group\Group $group
			 */
			$cachedUserGroups = array();
		});
	}

	/**
	 * @param \OC_Group_Backend $backend
	 */
	public function addBackend($backend) {
		$this->backends[] = $backend;
	}

	public function clearBackends() {
		$this->backends = array();
		$this->cachedGroups = array();
	}

	/**
	 * @param string $gid
	 * @return \OC\Group\Group
	 */
	public function get($gid) {
		if (isset($this->cachedGroups[$gid])) {
			return $this->cachedGroups[$gid];
		}
		return $this->getGroupObject($gid);
	}

	protected function getGroupObject($gid) {
		$backends = array();
		foreach ($this->backends as $backend) {
			if ($backend->groupExists($gid)) {
				$backends[] = $backend;
			}
		}
		if (count($backends) === 0) {
			return null;
		}
		$this->cachedGroups[$gid] = new Group($gid, $backends, $this->userManager, $this);
		return $this->cachedGroups[$gid];
	}

	/**
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid) {
		return !is_null($this->get($gid));
	}

	/**
	 * @param string $gid
	 * @return \OC\Group\Group
	 */
	public function createGroup($gid) {
		if ($gid === '' || is_null($gid)) {
			return false;
		} else if ($group = $this->get($gid)) {
			return $group;
		} else {
			$this->emit('\OC\Group', 'preCreate', array($gid));
			foreach ($this->backends as $backend) {
				if ($backend->implementsActions(\OC_Group_Backend::CREATE_GROUP)) {
					$backend->createGroup($gid);
					$group = $this->getGroupObject($gid);
					$this->emit('\OC\Group', 'postCreate', array($group));
					return $group;
				}
			}
			return null;
		}
	}

	/**
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return \OC\Group\Group[]
	 */
	public function search($search, $limit = null, $offset = null) {
		$groups = array();
		foreach ($this->backends as $backend) {
			$groupIds = $backend->getGroups($search, $limit, $offset);
			foreach ($groupIds as $groupId) {
				$groups[$groupId] = $this->get($groupId);
			}
			if (!is_null($limit) and $limit <= 0) {
				return array_values($groups);
			}
		}
		return array_values($groups);
	}

	/**
	 * @param \OC\User\User $user
	 * @return \OC\Group\Group[]
	 */
	public function getUserGroups($user) {
		return $this->getUserIdGroups($user->getUID());
	}

	/**
	 * @param string $uid the user id
	 * @return \OC\Group\Group[]
	 */
	public function getUserIdGroups($uid) {
		if (isset($this->cachedUserGroups[$uid])) {
			return $this->cachedUserGroups[$uid];
		}
		$groups = array();
		foreach ($this->backends as $backend) {
			$groupIds = $backend->getUserGroups($uid);
			foreach ($groupIds as $groupId) {
				$groups[$groupId] = $this->get($groupId);
			}
		}
		$this->cachedUserGroups[$uid] = $groups;
		return $this->cachedUserGroups[$uid];
	}

	/**
	 * Checks if a userId is in the admin group
	 * @param string $userId
	 * @return bool if admin
	 */
	public function isAdmin($userId) {
		return $this->isInGroup($userId, 'admin');
	}

	/**
	 * Checks if a userId is in a group
	 * @param string $userId
	 * @param group $group
	 * @return bool if in group
	 */
	public function isInGroup($userId, $group) {
		return array_key_exists($group, $this->getUserIdGroups($userId));
	}

	/**
	 * get a list of group ids for a user
	 * @param \OC\User\User $user
	 * @return array with group ids
	 */
	public function getUserGroupIds($user) {
		$groupIds = array();
		$userId = $user->getUID();
		if (isset($this->cachedUserGroups[$userId])) {
			return array_keys($this->cachedUserGroups[$userId]);
		} else {
			foreach ($this->backends as $backend) {
				$groupIds = array_merge($groupIds, $backend->getUserGroups($userId));
			}
		}
		return $groupIds;
	}

	/**
	 * get a list of all display names in a group
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of display names (value) and user ids (key)
	 */
	public function displayNamesInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		$group = $this->get($gid);
		if(is_null($group)) {
			return array();
		}

		$search = trim($search);
		$groupUsers = array();

		if(!empty($search)) {
			// only user backends have the capability to do a complex search for users
			$searchOffset = 0;
			$searchLimit = $limit * 100;
			if($limit === -1) {
				$searchLimit = 500;
			}

			do {
				$filteredUsers = $this->userManager->search($search, $searchLimit, $searchOffset);
				foreach($filteredUsers as $filteredUser) {
					if($group->inGroup($filteredUser)) {
						$groupUsers[]= $filteredUser;
					}
				}
				$searchOffset += $searchLimit;
			} while(count($groupUsers) < $searchLimit+$offset && count($filteredUsers) >= $searchLimit);

			if($limit === -1) {
				$groupUsers = array_slice($groupUsers, $offset);
			} else {
				$groupUsers = array_slice($groupUsers, $offset, $limit);
			}
		} else {
			$groupUsers = $group->searchUsers('', $limit, $offset);
		}

		$matchingUsers = array();
		foreach($groupUsers as $groupUser) {
			$matchingUsers[$groupUser->getUID()] = $groupUser->getDisplayName();
		}
		return $matchingUsers;
	}
}
