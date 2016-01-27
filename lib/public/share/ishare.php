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

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IUser;
use OCP\IGroup;

/**
 * Interface IShare
 *
 * @package OCP\Share
 * @since 9.0.0
 */
interface IShare {

	/**
	 * Get the id of the share
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getId();

	/**
	 * Set the id of the share
	 *
	 * @param string $id
	 * @return \OCP\Share\IShare The modified share object
	 * @since 9.0.0
	 */
	public function setId($id);

	/**
	 * Get the full share id
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getFullId();

	/**
	 * Set the provider id
	 *
	 * @param string $id
	 * @return \OCP\Share\IShare The modified share object\
	 * @since 9.0.0
	 */
	public function setProviderId($id);

	/**
	 * Set the path of this share
	 *
	 * @param Node $path
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setPath(Node $path);

	/**
	 * Get the path of this share for the current user
	 *
	 * @return File|Folder
	 * @since 9.0.0
	 */
	public function getPath();

	/**
	 * Set the shareType
	 *
	 * @param int $shareType
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setShareType($shareType);

	/**
	 * Get the shareType
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function getShareType();

	/**
	 * Set the receiver of this share
	 *
	 * @param IUser|IGroup|string
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setSharedWith($sharedWith);

	/**
	 * Get the receiver of this share
	 *
	 * @return IUser|IGroup|string
	 * @since 9.0.0
	 */
	public function getSharedWith();

	/**
	 * Set the permissions
	 *
	 * @param int $permissions
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setPermissions($permissions);

	/**
	 * Get the share permissions
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function getPermissions();

	/**
	 * Set the expiration date
	 *
	 * @param \DateTime $expireDate
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setExpirationDate($expireDate);

	/**
	 * Get the share expiration date
	 *
	 * @return \DateTime
	 * @since 9.0.0
	 */
	public function getExpirationDate();

	/**
	 * Set the sharer of the path
	 *
	 * @param IUser|string $sharedBy
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setSharedBy($sharedBy);

	/**
	 * Get share sharer
	 *
	 * @return IUser|string
	 * @since 9.0.0
	 */
	public function getSharedBy();

	/**
	 * Set the original share owner (who owns the path)
	 *
	 * @param IUser|string
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setShareOwner($shareOwner);

	/**
	 * Get the original share owner (who owns the path)
	 *
	 * @return IUser|string
	 * @since 9.0.0
	 */
	public function getShareOwner();

	/**
	 * Set the password
	 *
	 * @param string $password
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setPassword($password);

	/**
	 * Is a password set for this share
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getPassword();

	/**
	 * Set the token
	 *
	 * @param string $token
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setToken($token);

	/**
	 * Get the token
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getToken();

	/**
	 * Get the parent it
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function getParent();

	/**
	 * Set the target of this share
	 *
	 * @param string $target
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setTarget($target);

	/**
	 * Get the target of this share
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getTarget();

	/**
	 * Set the time this share was created
	 *
	 * @param int $shareTime
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setShareTime($shareTime);

	/**
	 * Get the timestamp this share was created
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function getShareTime();

	/**
	 * Set mailSend
	 *
	 * @param bool $mailSend
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setMailSend($mailSend);

	/**
	 * Get mailSend
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function getMailSend();
}
