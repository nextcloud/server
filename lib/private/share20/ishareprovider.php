<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
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
namespace OC\Share20;

use OC\Share20\Exception\ShareNotFound;
use OC\Share20\Exception\BackendError;
use OCP\IUser;

interface IShareProvider {

	/**
	 * Return the identifier of this provider.
	 *
	 * @return string Containing only [a-zA-Z0-9]
	 */
	public function identifier();

	/**
	 * Share a path
	 * 
	 * @param IShare $share
	 * @return IShare The share object
	 */
	public function create(IShare $share);

	/**
	 * Update a share
	 *
	 * @param IShare $share
	 * @return IShare The share object
	 */
	public function update(IShare $share);

	/**
	 * Delete a share
	 *
	 * @param IShare $share
	 * @throws BackendError
	 */
	public function delete(IShare $share);

	/**
	 * Unshare a file from self as recipient.
	 * This may require special handling.
	 *
	 * @param IShare $share
	 * @param IUser $recipient
	 */
	public function deleteFromSelf(IShare $share, IUser $recipient);

	/**
	 * Get all shares by the given user
	 *
	 * @param IUser $user
	 * @param int $shareType
	 * @param \OCP\Files\File|\OCP\Files\Folder $node
	 * @param bool $reshares Also get the shares where $user is the owner instead of just the shares where $user is the initiator
	 * @param int $limit The maximum number of shares to be returned, -1 for all shares
	 * @param int $offset
	 * @return Share[]
	 */
	public function getSharesBy(IUser $user, $shareType, $node, $reshares, $limit, $offset);

	/**
	 * Get share by id
	 *
	 * @param int $id
	 * @return IShare
	 * @throws ShareNotFound
	 */
	public function getShareById($id);

	/**
	 * Get children
	 *
	 * @param IShare $parent
	 * @return IShare[]
	 */
	public function getChildren(IShare $parent);

	/**
	 * Get shares for a given path
	 *
	 * @param \OCP\Files\Node $path
	 * @return IShare[]
	 */
	public function getSharesByPath(\OCP\Files\Node $path);

	/**
	 * Get shared with the given user
	 *
	 * @param IUser $user get shares where this user is the recipient
	 * @param int $shareType
	 * @param int $limit The max number of entries returned, -1 for all
	 * @param int $offset
	 * @param Share
	 */
	public function getSharedWith(IUser $user, $shareType, $limit, $offset);

	/**
	 * Get a share by token
	 *
	 * @param string $token
	 * @return IShare
	 * @throws ShareNotFound
	 */
	public function getShareByToken($token);
}
