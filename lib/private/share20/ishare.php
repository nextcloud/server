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
	 * Set the id of the share
	 *
	 * @param string $id
	 * @return IShare The modified share object
	 */
	public function setId($id);

	/**
	 * Get the full share id
	 *
	 * @return string
	 */
	public function getFullId();

	/**
	 * Set the provider id
	 *
	 * @param string $id
	 * @return IShare The modified share object
	 */
	public function setProviderId($id);

	/**
	 * Set the path of this share
	 *
	 * @param Node $path
	 * @return IShare The modified object
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
	 * @return IShare The modified object
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
	 * @return IShare The modified object
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
	 * @return IShare The modified object
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
	 * @return IShare The modified object
	 */
	public function setExpirationDate($expireDate);

	/**
	 * Get the share expiration date
	 *
	 * @return \DateTime
	 */
	public function getExpirationDate();

	/**
	 * Set the sharer of the path
	 *
	 * @param IUser|string $sharedBy
	 * @return IShare The modified object
	 */
	public function setSharedBy($sharedBy);

	/**
	 * Get share sharer
	 *
	 * @return IUser|string
	 */
	public function getSharedBy();

	/**
	 * Set the original share owner (who owns the path)
	 *
	 * @param IUser|string
	 *
	 * @return IShare The modified object
	 */
	public function setShareOwner($shareOwner);

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
	 * @return IShare The modified object
	 */
	public function setPassword($password);

	/**
	 * Is a password set for this share
	 *
	 * @return string
	 */
	public function getPassword();

	/**
	 * Set the token
	 *
	 * @param string $token
	 * @return IShare The modified object
	 */
	public function setToken($token);

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
	 * Set the target of this share
	 *
	 * @param string $target
	 * @return IShare The modified object
	 */
	public function setTarget($target);

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
