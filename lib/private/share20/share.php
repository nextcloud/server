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

use OCP\Files\Node;
use OCP\IUser;
use OCP\IGroup;

class Share {

	/** @var string */
	private $internalId;

	/** @var string */
	private $providerId;

	/** @var Node */
	private $path;

	/** @var int */
	private $shareType;

	/** @var IUser|IGroup|string */
	private $shareWith;

	/** @var IUser|string */
	private $sharedBy;

	/** @var IUser|string */
	private $shareOwner;

	/** @var int */
	private $permissions;

	/** @var \DateTime */
	private $expireDate;

	/** @var string */
	private $password;

	/**
	 * Set the id of the ShareProvider
	 * Should only be used by the share manager
	 *
	 * @param string $providerId
	 * @return Share The modified object
	 */
	public function setProviderId($providerId) {
		$this->providerId = $providerId;
		return $this;
	}

	/**
	 * Get the id of the ShareProvider
	 *
	 * @return string
	 */
	public function getProviderId() {
		return $this->providerId;
	}

	/**
	 * Set the internal (to the provider) share id
	 * Should only be used by the share provider
	 *
	 * @param string $id
	 * @return Share The modified object
	 */
	public function setInternalId($id) {
		$this->internalId = $id;
		return $this;
	}

	/**
	 * Get the internal (to the provider) share id
	 * Should only be used by the share provider
	 *
	 * @return string
	 */
	public function getInternalId() {
		return $this->internalId;
	}

	/**
	 * Get the id of the share
	 *
	 * @return string
	 */
	public function getId() {
		//TODO $id should be set as well as $providerId
		return $this->providerId . ':' . $this->internalId;
	}

	/**
	 * Set the path of this share
	 *
	 * @param Node $path
	 * @return Share The modified object
	 */
	public function setPath(Node $path) {
		$this->path = $path;
		return $this;
	}

	/**
	 * Get the path of this share for the current user
	 * 
	 * @return Node
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Set the shareType
	 *
	 * @param int $shareType
	 * @return Share The modified object
	 */
	public function setShareType($shareType) {
		$this->shareType = $shareType;
		return $this;
	}

	/**
	 * Get the shareType 
	 *
	 * @return int
	 */
	public function getShareType() {
		return $this->shareType;
	}

	/**
	 * Set the shareWith
	 *
	 * @param IUser|IGroup|string
	 * @return Share The modified object
	 */
	public function setShareWith($shareWith) {
		$this->shareWith = $shareWith;
		return $this;
	}

	/**
	 * Get the shareWith
	 *
	 * @return IUser|IGroup|string
	 */
	public function getShareWith() {
		return $this->shareWith;
	}

	/**
	 * Set the permissions
	 *
	 * @param int $permissions
	 * @return Share The modified object
	 */
	public function setPermissions($permissions) {
		//TODO checkes

		$this->permissions = $permissions;
		return $this;
	}

	/**
	 * Get the share permissions
	 *
	 * @return int
	 */
	public function getPermissions() {
		return $this->permissions;
	}

	/**
	 * Set the expiration date
	 *
	 * @param \DateTime $expireDate
	 * @return Share The modified object
	 */
	public function setExpirationDate(\DateTime $expireDate) {
		//TODO checks

		$this->expireDate = $expireDate;
		return $this;
	}

	/**
	 * Get the share expiration date
	 *
	 * @return \DateTime
	 */
	public function getExpirationDate() {
		return $this->expireDate;
	}

	/**
	 * Set the sharer of the path
	 *
	 * @param IUser|string $sharedBy
	 * @return Share The modified object
	 */
	public function setSharedBy($sharedBy) {
		//TODO checks
		$this->sharedBy = $sharedBy;

		return $this;
	}

	/**
	 * Get share sharer
	 *
	 * @return IUser|string
	 */
	public function getSharedBy() {
		//TODO check if set
		return $this->sharedBy;
	}

	/**
	 * Set the original share owner (who owns the path)
	 *
	 * @param IUser|string
	 *
	 * @return Share The modified object
	 */
	public function setShareOwner($shareOwner) {
		//TODO checks

		$this->shareOwner = $shareOwner;
		return $this;
	}

	/**
	 * Get the original share owner (who owns the path)
	 * 
	 * @return IUser|string
	 */
	public function getShareOwner() {
		//TODO check if set
		return $this->shareOwner;
	}

	/**
	 * Set the password
	 *
	 * @param string $password
	 *
	 * @return Share The modified object
	 */
	public function setPassword($password) {
		//TODO verify

		$this->password = $password;
		return $this;
	}

	/**
	 * Get the password
	 *
	 * @return string
	 */
	public function getPassword($password) {
		return $this->password;
	}
}
