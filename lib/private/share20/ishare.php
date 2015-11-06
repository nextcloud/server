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

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IUser;
use OCP\IGroup;

interface IShare {

	/**
	 * Get the id of the share
	 *
	 * @return string
	 */
	public function getId();

	/**
	 * Set the path of this share
	 *
	 * @param File|Folder $path
	 * @return Share The modified object
	 */
	public function setPath(Node $path);

	/**
	 * Get the path of this share for the current user
	 * 
	 * @return File|Folder
	 */
	public function getPath();

	/**
	 * Set the shareType
	 *
	 * @param int $shareType
	 * @return Share The modified object
	 */
	public function setShareType($shareType);

	/**
	 * Get the shareType 
	 *
	 * @return int
	 */
	public function getShareType();

	/**
	 * Set the receiver of this share
	 *
	 * @param IUser|IGroup|string
	 * @return Share The modified object
	 */
	public function setSharedWith($sharedWith);

	/**
	 * Get the receiver of this share
	 *
	 * @return IUser|IGroup|string
	 */
	public function getSharedWith();

	/**
	 * Set the permissions
	 *
	 * @param int $permissions
	 * @return Share The modified object
	 */
	public function setPermissions($permissions);

	/**
	 * Get the share permissions
	 *
	 * @return int
	 */
	public function getPermissions();

	/**
	 * Set the expiration date
	 *
	 * @param \DateTime $expireDate
	 * @return Share The modified object
	 */
	public function setExpirationDate(\DateTime $expireDate);

	/**
	 * Get the share expiration date
	 *
	 * @return \DateTime
	 */
	public function getExpirationDate();

	/**
	 * Get share sharer
	 *
	 * @return IUser|string
	 */
	public function getSharedBy();

	/**
	 * Get the original share owner (who owns the path)
	 * 
	 * @return IUser|string
	 */
	public function getShareOwner();

	/**
	 * Set the password
	 *
	 * @param string $password
	 *
	 * @return Share The modified object
	 */
	public function setPassword($password);

	/**
	 * Is a password set for this share
	 *
	 * @return string
	 */
	public function getPassword();

	/**
	 * Get the token
	 *
	 * @return string
	 */
	public function getToken();

	/**
	 * Get the parent it
	 *
	 * @return int
	 */
	public function getParent();

	/**
	 * Get the target of this share
	 *
	 * @return string
	 */
	public function getTarget();

	/**
	 * Get the timestamp this share was created
	 *
	 * @return int
	 */
	public function getSharetime();

	/**
	 * Get mailSend
	 *
	 * @return bool
	 */
	public function getMailSend();
}
