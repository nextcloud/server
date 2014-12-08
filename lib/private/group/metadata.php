<?php

/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Group;

class MetaData {
	const SORT_NONE = 0;
	const SORT_USERCOUNT = 1;

	/**
	 * @var string $user
	 */
	protected $user;

	/**
	 * @var bool $isAdmin
	 */
	protected $isAdmin;

	/**
	 * @var array $metaData
	 */
	protected $metaData = array();

	/**
	 * @var \OCP\IGroupManager $groupManager
	 */
	protected $groupManager;

	/**
	 * @var int $sorting
	 */
	protected $sorting = false;

	/**
	 * @param string $user the uid of the current user
	 * @param bool $isAdmin whether the current users is an admin
	 * @param \OCP\IGroupManager $groupManager
	 */
	public function __construct(
			$user,
			$isAdmin,
			\OCP\IGroupManager $groupManager
			) {
		$this->user = $user;
		$this->isAdmin = (bool)$isAdmin;
		$this->groupManager = $groupManager;
	}

	/**
	 * returns an array with meta data about all available groups
	 * the array is structured as follows:
	 * [0] array containing meta data about admin groups
	 * [1] array containing meta data about unprivileged groups
	 * @param string $groupSearch only effective when instance was created with
	 * isAdmin being true
	 * @param string $userSearch the pattern users are search for
	 * @return array
	 */
	public function get($groupSearch = '', $userSearch = '') {
		$key = $groupSearch . '::' . $userSearch;
		if(isset($this->metaData[$key])) {
			return $this->metaData[$key];
		}

		$adminGroups = array();
		$groups = array();
		$sortGroupsIndex = 0;
		$sortGroupsKeys = array();
		$sortAdminGroupsIndex = 0;
		$sortAdminGroupsKeys = array();

		foreach($this->getGroups($groupSearch) as $group) {
			$groupMetaData = $this->generateGroupMetaData($group, $userSearch);
			if (strtolower($group->getGID()) !== 'admin') {
				$this->addEntry(
					$groups,
					$sortGroupsKeys,
					$sortGroupsIndex,
					$groupMetaData);
			} else {
				//admin group is hard coded to 'admin' for now. In future,
				//backends may define admin groups too. Then the if statement
				//has to be adjusted accordingly.
				$this->addEntry(
					$adminGroups,
					$sortAdminGroupsKeys,
					$sortAdminGroupsIndex,
					$groupMetaData);
			}
		}

		//whether sorting is necessary is will be checked in sort()
		$this->sort($groups, $sortGroupsKeys);
		$this->sort($adminGroups, $sortAdminGroupsKeys);

		$this->metaData[$key] = array($adminGroups, $groups);
		return $this->metaData[$key];
	}

	/**
	 * sets the sort mode, currently 0 (none) and 1 (user entries,
	 * descending) are supported
	 * @param int $sortMode (SORT_NONE, SORT_USERCOUNT)
	 */
	public function setSorting($sortMode) {
		if($sortMode >= 0 && $sortMode <= 1) {
			$this->sorting = $sortMode;
		} else {
			$this->sorting = 0;
		}
	}

	/**
	 * adds an group entry to the resulting array
	 * @param array $entries the resulting array, by reference
	 * @param array $sortKeys the sort key array, by reference
	 * @param int $sortIndex the sort key index, by reference
	 * @param array $data the group's meta data as returned by generateGroupMetaData()
	 * @return null
	 */
	private function addEntry(&$entries, &$sortKeys, &$sortIndex, $data) {
		$entries[] = $data;
		if($this->sorting === 1) {
			$sortKeys[$sortIndex] = $data['usercount'];
			$sortIndex++;
		}
	}

	/**
	 * creates an array containing the group meta data
	 * @param \OC\Group\Group $group
	 * @param string $userSearch
	 * @return array with the keys 'id', 'name' and 'usercount'
	 */
	private function generateGroupMetaData(\OC\Group\Group $group, $userSearch) {
		return array(
				'id' => $group->getGID(),
				'name' => $group->getGID(),
				'usercount' => $group->count($userSearch)
			);
	}

	/**
	 * sorts the result array, if applicable
	 * @param array $entries the result array, by reference
	 * @param array $sortKeys the array containing the sort keys
	 * @param return null
	 */
	private function sort(&$entries, $sortKeys) {
		if($this->sorting > 0) {
			array_multisort($sortKeys, SORT_DESC, $entries);
		}
	}

	/**
	 * returns the available groups
	 * @param string $search a search string
	 * @return \OC\Group\Group[]
	 */
	private function getGroups($search = '') {
		if($this->isAdmin) {
			return $this->groupManager->search($search);
		} else {
			// FIXME: Remove static method call
			$groupIds = \OC_SubAdmin::getSubAdminsGroups($this->user);

			/* \OC_SubAdmin::getSubAdminsGroups() returns an array of GIDs, but this
			* method is expected to return an array with the GIDs as keys and group objects as
			* values, so we need to convert this information.
			*/
			$groups = array();
			foreach($groupIds as $gid) {
				$group = $this->groupManager->get($gid);
				if (!is_null($group)) {
					$groups[$gid] = $group;
				}
			}

			return $groups;
		}
	}
}
