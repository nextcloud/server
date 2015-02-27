<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Group;

use OCP\IGroup;

class Group implements IGroup {
	/**
	 * @var string $id
	 */
	private $gid;

	/**
	 * @var \OC\User\User[] $users
	 */
	private $users = array();

	/**
	 * @var bool $usersLoaded
	 */
	private $usersLoaded;

	/**
	 * @var \OC_Group_Backend[]|\OC_Group_Database[] $backend
	 */
	private $backends;

	/**
	 * @var \OC\Hooks\PublicEmitter $emitter
	 */
	private $emitter;

	/**
	 * @var \OC\User\Manager $userManager
	 */
	private $userManager;

	/**
	 * @param string $gid
	 * @param \OC_Group_Backend[] $backends
	 * @param \OC\User\Manager $userManager
	 * @param \OC\Hooks\PublicEmitter $emitter
	 */
	public function __construct($gid, $backends, $userManager, $emitter = null) {
		$this->gid = $gid;
		$this->backends = $backends;
		$this->userManager = $userManager;
		$this->emitter = $emitter;
	}

	public function getGID() {
		return $this->gid;
	}

	/**
	 * get all users in the group
	 *
	 * @return \OC\User\User[]
	 */
	public function getUsers() {
		if ($this->usersLoaded) {
			return $this->users;
		}

		$userIds = array();
		foreach ($this->backends as $backend) {
			$diff = array_diff(
				$backend->usersInGroup($this->gid),
				$userIds
			);
			if ($diff) {
				$userIds = array_merge($userIds, $diff);
			}
		}

		$this->users = $this->getVerifiedUsers($userIds);
		$this->usersLoaded = true;
		return $this->users;
	}

	/**
	 * check if a user is in the group
	 *
	 * @param \OC\User\User $user
	 * @return bool
	 */
	public function inGroup($user) {
		if (isset($this->users[$user->getUID()])) {
			return true;
		}
		foreach ($this->backends as $backend) {
			if ($backend->inGroup($user->getUID(), $this->gid)) {
				$this->users[$user->getUID()] = $user;
				return true;
			}
		}
		return false;
	}

	/**
	 * add a user to the group
	 *
	 * @param \OC\User\User $user
	 */
	public function addUser($user) {
		if ($this->inGroup($user)) {
			return;
		}

		if ($this->emitter) {
			$this->emitter->emit('\OC\Group', 'preAddUser', array($this, $user));
		}
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(\OC_Group_Backend::ADD_TO_GROUP)) {
				$backend->addToGroup($user->getUID(), $this->gid);
				if ($this->users) {
					$this->users[$user->getUID()] = $user;
				}
				if ($this->emitter) {
					$this->emitter->emit('\OC\Group', 'postAddUser', array($this, $user));
				}
				return;
			}
		}
	}

	/**
	 * remove a user from the group
	 *
	 * @param \OC\User\User $user
	 */
	public function removeUser($user) {
		$result = false;
		if ($this->emitter) {
			$this->emitter->emit('\OC\Group', 'preRemoveUser', array($this, $user));
		}
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(\OC_Group_Backend::REMOVE_FROM_GOUP) and $backend->inGroup($user->getUID(), $this->gid)) {
				$backend->removeFromGroup($user->getUID(), $this->gid);
				$result = true;
			}
		}
		if ($result) {
			if ($this->emitter) {
				$this->emitter->emit('\OC\Group', 'postRemoveUser', array($this, $user));
			}
			if ($this->users) {
				foreach ($this->users as $index => $groupUser) {
					if ($groupUser->getUID() === $user->getUID()) {
						unset($this->users[$index]);
						return;
					}
				}
			}
		}
	}

	/**
	 * search for users in the group by userid
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return \OC\User\User[]
	 */
	public function searchUsers($search, $limit = null, $offset = null) {
		$users = array();
		foreach ($this->backends as $backend) {
			$userIds = $backend->usersInGroup($this->gid, $search, $limit, $offset);
			$users += $this->getVerifiedUsers($userIds);
			if (!is_null($limit) and $limit <= 0) {
				return array_values($users);
			}
		}
		return array_values($users);
	}

	/**
	 * returns the number of users matching the search string
	 *
	 * @param string $search
	 * @return int|bool
	 */
	public function count($search = '') {
		$users = false;
		foreach ($this->backends as $backend) {
			if($backend->implementsActions(\OC_Group_Backend::COUNT_USERS)) {
				if($users === false) {
					//we could directly add to a bool variable, but this would
					//be ugly
					$users = 0;
				}
				$users += $backend->countUsersInGroup($this->gid, $search);
			}
		}
		return $users;
	}

	/**
	 * search for users in the group by displayname
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return \OC\User\User[]
	 */
	public function searchDisplayName($search, $limit = null, $offset = null) {
		$users = array();
		foreach ($this->backends as $backend) {
			$userIds = $backend->usersInGroup($this->gid, $search, $limit, $offset);
			$users = $this->getVerifiedUsers($userIds);
			if (!is_null($limit) and $limit <= 0) {
				return array_values($users);
			}
		}
		return array_values($users);
	}

	/**
	 * delete the group
	 *
	 * @return bool
	 */
	public function delete() {
		// Prevent users from deleting group admin
		if ($this->getGID() === 'admin') {
			return false;
		}

		$result = false;
		if ($this->emitter) {
			$this->emitter->emit('\OC\Group', 'preDelete', array($this));
		}
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(\OC_Group_Backend::DELETE_GROUP)) {
				$result = true;
				$backend->deleteGroup($this->gid);
			}
		}
		if ($result and $this->emitter) {
			$this->emitter->emit('\OC\Group', 'postDelete', array($this));
		}
		return $result;
	}

	/**
	 * returns all the Users from an array that really exists
	 * @param string[] $userIds an array containing user IDs
	 * @return \OC\User\User[] an Array with the userId as Key and \OC\User\User as value
	 */
	private function getVerifiedUsers($userIds) {
		if (!is_array($userIds)) {
			return array();
		}
		$users = array();
		foreach ($userIds as $userId) {
			$user = $this->userManager->get($userId);
			if (!is_null($user)) {
				$users[$userId] = $user;
			}
		}
		return $users;
	}
}
