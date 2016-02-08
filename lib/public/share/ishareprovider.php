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
use OCP\Files\Node;
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
	 * Create a share
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
	 * This may require special handling. If a user unshares a group
	 * share from their self then the original group share should still exist.
	 *
	 * @param \OCP\Share\IShare $share
	 * @param string $recipient UserId of the recipient
	 * @since 9.0.0
	 */
	public function deleteFromSelf(\OCP\Share\IShare $share, $recipient);

	/**
	 * Move a share as a recipient.
	 * This is updating the share target. Thus the mount point of the recipient.
	 * This may require special handling. If a user moves a group share
	 * the target should only be changed for them.
	 *
	 * @param \OCP\Share\IShare $share
	 * @param string $recipient userId of recipient
	 * @return \OCP\Share\IShare
	 * @since 9.0.0
	 */
	public function move(\OCP\Share\IShare $share, $recipient);

	/**
	 * Get all shares by the given user
	 *
	 * @param string $userId
	 * @param int $shareType
	 * @param \OCP\Files\File|\OCP\Files\Folder $node
	 * @param bool $reshares Also get the shares where $user is the owner instead of just the shares where $user is the initiator
	 * @param int $limit The maximum number of shares to be returned, -1 for all shares
	 * @param int $offset
	 * @return \OCP\Share\IShare[]
	 * @since 9.0.0
	 */
	public function getSharesBy($userId, $shareType, $node, $reshares, $limit, $offset);

	/**
	 * Get share by id
	 *
	 * @param int $id
	 * @param string|null $recipientId
	 * @return \OCP\Share\IShare
	 * @throws ShareNotFound
	 * @since 9.0.0
	 */
	public function getShareById($id, $recipientId = null);

	/**
	 * Get shares for a given path
	 *
	 * @param \OCP\Files\Node $path
	 * @return \OCP\Share\IShare[]
	 * @since 9.0.0
	 */
	public function getSharesByPath(\OCP\Files\Node $path);

	/**
	 * Get shared with the given user
	 *
	 * @param string $userId get shares where this user is the recipient
	 * @param int $shareType
	 * @param Node|null $node
	 * @param int $limit The max number of entries returned, -1 for all
	 * @param int $offset
	 * @return \OCP\Share\IShare[]
	 * @since 9.0.0
	 */
	public function getSharedWith($userId, $shareType, $node, $limit, $offset);

	/**
	 * Get a share by token
	 *
	 * @param string $token
	 * @return \OCP\Share\IShare
	 * @throws ShareNotFound
	 * @since 9.0.0
	 */
	public function getShareByToken($token);
}
