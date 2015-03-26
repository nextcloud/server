<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
