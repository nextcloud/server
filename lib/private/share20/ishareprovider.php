<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
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
namespace OC\Share20;

use OCP\IUser;

interface IShareProvider {

	/**
	 * Share a path
	 * 
	 * @param Share $share
	 * @return Share The share object
	 */
	public function create(Share $share);

	/**
	 * Update a share
	 *
	 * @param Share $share
	 * @return Share The share object
	 */
	public function update(Share $share);

	/**
	 * Delete a share
	 *
	 * @param Share $share
	 */
	public function delete(Share $share);

	/**
	 * Get all shares by the given user
	 *
	 * @param IUser $user
	 * @param int $shareType
	 * @param int $offset
	 * @param int $limit
	 * @return Share[]
	 */
	public function getShares(IUser $user, $shareType, $offset, $limit);

	/**
	 * Get share by id
	 *
	 * @param int $id
	 * @return Share
	 */
	public function getShareById($id);

	/**
	 * Get shares for a given path
	 *
	 * @param \OCP\Files\Node $path
	 * @param Share[]
	 */
	public function getSharesByPath(\OCP\IUser $user, \OCP\Files\Node $path);

	/**
	 * Get shared with the given user
	 *
	 * @param IUser $user
	 * @param int $shareType
	 * @param Share
	 */
	public function getSharedWithMe(IUser $user, $shareType = null);

	/**
	 * Get a share by token and if present verify the password
	 *
	 * @param string $token
	 * @param string $password
	 * @param Share
	 */
	public function getShareByToken($token, $password = null);
}
