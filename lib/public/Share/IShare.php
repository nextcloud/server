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

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Share\Exceptions\IllegalIDChangeException;

/**
 * Interface IShare
 *
 * @package OCP\Share
 * @since 9.0.0
 */
interface IShare {

	/**
	 * Set the internal id of the share
	 * It is only allowed to set the internal id of a share once.
	 * Attempts to override the internal id will result in an IllegalIDChangeException
	 *
	 * @param string $id
	 * @return \OCP\Share\IShare
	 * @throws IllegalIDChangeException
	 * @throws \InvalidArgumentException
	 * @since 9.1.0
	 */
	public function setId($id);

	/**
	 * Get the internal id of the share.
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getId();

	/**
	 * Get the full share id. This is the <providerid>:<internalid>.
	 * The full id is unique in the system.
	 *
	 * @return string
	 * @since 9.0.0
	 * @throws \UnexpectedValueException If the fullId could not be constructed
	 */
	public function getFullId();

	/**
	 * Set the provider id of the share
	 * It is only allowed to set the provider id of a share once.
	 * Attempts to override the provider id will result in an IllegalIDChangeException
	 *
	 * @param string $id
	 * @return \OCP\Share\IShare
	 * @throws IllegalIDChangeException
	 * @throws \InvalidArgumentException
	 * @since 9.1.0
	 */
	public function setProviderId($id);

	/**
	 * Set the node of the file/folder that is shared
	 *
	 * @param Node $node
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setNode(Node $node);

	/**
	 * Get the node of the file/folder that is shared
	 *
	 * @return File|Folder
	 * @since 9.0.0
	 * @throws NotFoundException
	 */
	public function getNode();

	/**
	 * Set file id for lazy evaluation of the node
	 * @param int $fileId
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setNodeId($fileId);

	/**
	 * Get the fileid of the node of this share
	 * @return int
	 * @since 9.0.0
	 * @throws NotFoundException
	 */
	public function getNodeId();

	/**
	 * Set the type of node (file/folder)
	 *
	 * @param string $type
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setNodeType($type);

	/**
	 * Get the type of node (file/folder)
	 *
	 * @return string
	 * @since 9.0.0
	 * @throws NotFoundException
	 */
	public function getNodeType();

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
	 * Set the receiver of this share.
	 *
	 * @param string $sharedWith
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setSharedWith($sharedWith);

	/**
	 * Get the receiver of this share.
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getSharedWith();

	/**
	 * Set the permissions.
	 * See \OCP\Constants::PERMISSION_*
	 *
	 * @param int $permissions
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setPermissions($permissions);

	/**
	 * Get the share permissions
	 * See \OCP\Constants::PERMISSION_*
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
	 * Get the expiration date
	 *
	 * @return \DateTime
	 * @since 9.0.0
	 */
	public function getExpirationDate();

	/**
	 * Set the sharer of the path.
	 *
	 * @param string $sharedBy
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setSharedBy($sharedBy);

	/**
	 * Get share sharer
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getSharedBy();

	/**
	 * Set the original share owner (who owns the path that is shared)
	 *
	 * @param string $shareOwner
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setShareOwner($shareOwner);

	/**
	 * Get the original share owner (who owns the path that is shared)
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getShareOwner();

	/**
	 * Set the password for this share.
	 * When the share is passed to the share manager to be created
	 * or updated the password will be hashed.
	 *
	 * @param string $password
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setPassword($password);

	/**
	 * Get the password of this share.
	 * If this share is obtained via a shareprovider the password is
	 * hashed.
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getPassword();

	/**
	 * Set the public link token.
	 *
	 * @param string $token
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setToken($token);

	/**
	 * Get the public link token.
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getToken();

	/**
	 * Set the target path of this share relative to the recipients user folder.
	 *
	 * @param string $target
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setTarget($target);

	/**
	 * Get the target path of this share relative to the recipients user folder.
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getTarget();

	/**
	 * Set the time this share was created
	 *
	 * @param \DateTime $shareTime
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setShareTime(\DateTime $shareTime);

	/**
	 * Get the timestamp this share was created
	 *
	 * @return \DateTime
	 * @since 9.0.0
	 */
	public function getShareTime();

	/**
	 * Set if the recipient is informed by mail about the share.
	 *
	 * @param bool $mailSend
	 * @return \OCP\Share\IShare The modified object
	 * @since 9.0.0
	 */
	public function setMailSend($mailSend);

	/**
	 * Get if the recipient informed by mail about the share.
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function getMailSend();
}
