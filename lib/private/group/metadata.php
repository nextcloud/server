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
	 * @var string[] $groups
	 */
	protected $groups = array();

	/**
	 * @var \OC\Group\Manager $groupManager
	 */
	protected $groupManager;

	/**
	 * @var int $sorting
	 */
	protected $sorting = false;

	/**
	 * @var string $lastSearch
	 */
	protected $lastSearch;

	/**
	 * @param string the uid of the current user
	 * @param bool whether the current users is an admin
	 * @param \OC\Group\Manager
	 */
	public function __construct(
			$user,
			$isAdmin,
			\OC\Group\Manager $groupManager
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
	 * @param string only effective when instance was created with isAdmin being
	 * true
	 * @return array
	 */
	public function get($search = '') {
		if($this->lastSearch !== $search) {
			$this->lastSearch = $search;
			$this->groups = array();
		}

		$adminGroups = array();
		$groups = array();
		$sortGroupsIndex = 0;
		$sortGroupsKeys = array();
		$sortAdminGroupsIndex = 0;
		$sortAdminGroupsKeys = array();

		foreach($this->getGroups($search) as $group) {
			$usersInGroup = $group->count();
			$groupMetaData = $this->generateGroupMetaData($group);
			if (strtolower($gid) !== 'admin') {
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

		return array($adminGroups, $groups);
	}

	/**
	 * @brief sets the sort mode, currently 0 (none) and 1 (user entries,
	 * descalating) are supported
	 * @param int the sortMode (SORT_NONE, SORT_USERCOUNT)
	 */
	public function setSorting($sortMode) {
		if($sortMode >= 0 && $sortMode <= 1) {
			$this->sorting = $sortMode;
		} else {
			$this->sorting = 0;
		}
	}

	/**
	 * @brief adds an group entry to the resulting array
	 * @param array the resulting array, by reference
	 * @param array the sort key array, by reference
	 * @param array the sort key index, by reference
	 * @param array the group's meta data as returned by generateGroupMetaData()
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
	 * @brief creates an array containing the group meta data
	 * @param \OC\Group\Group
	 * @return array with the keys 'id', 'name' and 'usercount'
	 */
	private function generateGroupMetaData(\OC\Group\Group $group) {
		return array(
				'id' => str_replace(' ','', $group->getGID()),
				'name' => $group->getGID(),
				'usercount' => $group->count()
			);
	}

	/**
	 * @brief sorts the result array, if applicable
	 * @param array the result array, by reference
	 * @param array the array containing the sort keys
	 * @param return null
	 */
	private function sort(&$entries, $sortKeys) {
		if($this->sorting > 0) {
			array_multisort($sortKeys, SORT_DESC, $entries);
		}
	}

	/**
	 * @brief returns the available groups
	 * @param string a search string
	 * @return string[]
	 */
	private function getGroups($search = '') {
		if(count($this->groups) === 0) {
			$this->fetchGroups($search);
		}
		return $this->groups;
	}

	/**
	 * @brief fetches the group using the group manager or the subAdmin API
	 * @param string a search string
	 * @return null
	 */
	private function fetchGroups($search = '') {
		if($this->isAdmin) {
			$this->groups = $this->groupManager->search($search);
		} else {
			$this->groups = \OC_SubAdmin::getSubAdminsGroups($this->user);
		}
	}
}