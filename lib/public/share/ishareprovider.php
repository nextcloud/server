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

namespace OCP\Share;

use OC\Share20\Exception\ShareNotFound;
use OC\Share20\Exception\BackendError;
use OCP\IUser;

/**
 * Interface IShareProvider
 *
 * @package OCP\Share
 * @since 9.0.0
 */
interface IShareProvider {

	/**
	 * Return the identifier of this provider.
	 *
	 * @return string Containing only [a-zA-Z0-9]
	 * @since 9.0.0
	 */
	public function identifier();

	/**
	 * Share a path
	 * 
	 * @param \OCP\Share\IShare $share
	 * @return \OCP\Share\IShare The share object
	 * @since 9.0.0
	 */
	public function create(\OCP\Share\IShare $share);

	/**
	 * Update a share
	 *
	 * @param \OCP\Share\IShare $share
	 * @return \OCP\Share\IShare The share object
	 * @since 9.0.0
	 */
	public function update(\OCP\Share\IShare $share);

	/**
	 * Delete a share
	 *
	 * @param \OCP\Share\IShare $share
	 * @since 9.0.0
	 */
	public function delete(\OCP\Share\IShare $share);

	/**
	 * Unshare a file from self as recipient.
	 * This may require special handling.
	 *
	 * @param \OCP\Share\IShare $share
	 * @param IUser $recipient
	 * @since 9.0.0
	 */
	public function deleteFromSelf(\OCP\Share\IShare $share, IUser $recipient);

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
	 * @since 9.0.0
	 */
	public function getSharesBy(IUser $user, $shareType, $node, $reshares, $limit, $offset);

	/**
	 * Get share by id
	 *
	 * @param int $id
	 * @return IShare
	 * @throws ShareNotFound
	 * @since 9.0.0
	 */
	public function getShareById($id);

	/**
	 * Get shares for a given path
	 *
	 * @param \OCP\Files\Node $path
	 * @return IShare[]
	 * @since 9.0.0
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
	 * @since 9.0.0
	 */
	public function getSharedWith(IUser $user, $shareType, $limit, $offset);

	/**
	 * Get a share by token
	 *
	 * @param string $token
	 * @return IShare
	 * @throws ShareNotFound
	 * @since 9.0.0
	 */
	public function getShareByToken($token);
}
