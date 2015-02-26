<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

interface IGroup {
	/**
	 * @return string
	 */
	public function getGID();

	/**
	 * get all users in the group
	 *
	 * @return \OCP\IUser[]
	 */
	public function getUsers();

	/**
	 * check if a user is in the group
	 *
	 * @param \OCP\IUser $user
	 * @return bool
	 */
	public function inGroup($user);

	/**
	 * add a user to the group
	 *
	 * @param \OCP\IUser $user
	 */
	public function addUser($user);

	/**
	 * remove a user from the group
	 *
	 * @param \OCP\IUser $user
	 */
	public function removeUser($user);

	/**
	 * search for users in the group by userid
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return \OCP\IUser[]
	 */
	public function searchUsers($search, $limit = null, $offset = null);

	/**
	 * returns the number of users matching the search string
	 *
	 * @param string $search
	 * @return int|bool
	 */
	public function count($search = '');

	/**
	 * search for users in the group by displayname
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return \OCP\IUser[]
	 */
	public function searchDisplayName($search, $limit = null, $offset = null);

	/**
	 * delete the group
	 *
	 * @return bool
	 */
	public function delete();
}
