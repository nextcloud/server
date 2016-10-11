<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
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

use OCP\Files\Node;

use OCP\Share\Exceptions\ShareNotFound;

/**
 * Interface IManager
 *
 * @package OCP\Share
 * @since 9.0.0
 */
interface IManager {

	/**
	 * Create a Share
	 *
	 * @param IShare $share
	 * @return IShare The share object
	 * @since 9.0.0
	 */
	public function createShare(IShare $share);

	/**
	 * Update a share.
	 * The target of the share can't be changed this way: use moveShare
	 * The share can't be removed this way (permission 0): use deleteShare
	 *
	 * @param IShare $share
	 * @return IShare The share object
	 * @since 9.0.0
	 */
	public function updateShare(IShare $share);

	/**
	 * Delete a share
	 *
	 * @param IShare $share
	 * @throws ShareNotFound
	 * @since 9.0.0
	 */
	public function deleteShare(IShare $share);

	/**
	 * Unshare a file as the recipient.
	 * This can be different from a regular delete for example when one of
	 * the users in a groups deletes that share. But the provider should
	 * handle this.
	 *
	 * @param IShare $share
	 * @param string $recipientId
	 * @since 9.0.0
	 */
	public function deleteFromSelf(IShare $share, $recipientId);

	/**
	 * Move the share as a recipient of the share.
	 * This is updating the share target. So where the recipient has the share mounted.
	 *
	 * @param IShare $share
	 * @param string $recipientId
	 * @return IShare
	 * @throws \InvalidArgumentException If $share is a link share or the $recipient does not match
	 * @since 9.0.0
	 */
	public function moveShare(IShare $share, $recipientId);

	/**
	 * Get shares shared by (initiated) by the provided user.
	 *
	 * @param string $userId
	 * @param int $shareType
	 * @param Node|null $path
	 * @param bool $reshares
	 * @param int $limit The maximum number of returned results, -1 for all results
	 * @param int $offset
	 * @return IShare[]
	 * @since 9.0.0
	 */
	public function getSharesBy($userId, $shareType, $path = null, $reshares = false, $limit = 50, $offset = 0);

	/**
	 * Get shares shared with $user.
	 * Filter by $node if provided
	 *
	 * @param string $userId
	 * @param int $shareType
	 * @param Node|null $node
	 * @param int $limit The maximum number of shares returned, -1 for all
	 * @param int $offset
	 * @return IShare[]
	 * @since 9.0.0
	 */
	public function getSharedWith($userId, $shareType, $node = null, $limit = 50, $offset = 0);

	/**
	 * Retrieve a share by the share id.
	 * If the recipient is set make sure to retrieve the file for that user.
	 * This makes sure that if a user has moved/deleted a group share this
	 * is reflected.
	 *
	 * @param string $id
	 * @param string|null $recipient userID of the recipient
	 * @return IShare
	 * @throws ShareNotFound
	 * @since 9.0.0
	 */
	public function getShareById($id, $recipient = null);

	/**
	 * Get the share by token possible with password
	 *
	 * @param string $token
	 * @return IShare
	 * @throws ShareNotFound
	 * @since 9.0.0
	 */
	public function getShareByToken($token);

	/**
	 * Verify the password of a public share
	 *
	 * @param IShare $share
	 * @param string $password
	 * @return bool
	 * @since 9.0.0
	 */
	public function checkPassword(IShare $share, $password);

	/**
	 * The user with UID is deleted.
	 * All share providers have to cleanup the shares with this user as well
	 * as shares owned by this user.
	 * Shares only initiated by this user are fine.
	 *
	 * @param string $uid
	 * @since 9.1.0
	 */
	public function userDeleted($uid);

	/**
	 * The group with $gid is deleted
	 * We need to clear up all shares to this group
	 *
	 * @param string $gid
	 * @since 9.1.0
	 */
	public function groupDeleted($gid);

	/**
	 * The user $uid is deleted from the group $gid
	 * All user specific group shares have to be removed
	 *
	 * @param string $uid
	 * @param string $gid
	 * @since 9.1.0
	 */
	public function userDeletedFromGroup($uid, $gid);

	/**
	 * Instantiates a new share object. This is to be passed to
	 * createShare.
	 *
	 * @return IShare
	 * @since 9.0.0
	 */
	public function newShare();

	/**
	 * Is the share API enabled
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function shareApiEnabled();

	/**
	 * Is public link sharing enabled
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function shareApiAllowLinks();

	/**
	 * Is password on public link requires
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function shareApiLinkEnforcePassword();

	/**
	 * Is default expire date enabled
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function shareApiLinkDefaultExpireDate();

	/**
	 * Is default expire date enforced
	 *`
	 * @return bool
	 * @since 9.0.0
	 */
	public function shareApiLinkDefaultExpireDateEnforced();

	/**
	 * Number of default expire days
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function shareApiLinkDefaultExpireDays();

	/**
	 * Allow public upload on link shares
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function shareApiLinkAllowPublicUpload();

	/**
	 * check if user can only share with group members
	 * @return bool
	 * @since 9.0.0
	 */
	public function shareWithGroupMembersOnly();

	/**
	 * Check if users can share with groups
	 * @return bool
	 * @since 9.0.1
	 */
	public function allowGroupSharing();

	/**
	 * Check if sharing is disabled for the given user
	 *
	 * @param string $userId
	 * @return bool
	 * @since 9.0.0
	 */
	public function sharingDisabledForUser($userId);

	/**
	 * Check if outgoing server2server shares are allowed
	 * @return bool
	 * @since 9.0.0
	 */
	public function outgoingServer2ServerSharesAllowed();

}
