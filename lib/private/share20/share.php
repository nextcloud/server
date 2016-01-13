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

use OCP\Files\Node;
use OCP\IUser;
use OCP\IGroup;

class Share implements IShare {

	/** @var string */
	private $id;
	/** @var string */
	private $providerId;
	/** @var Node */
	private $path;
	/** @var int */
	private $shareType;
	/** @var IUser|IGroup|string */
	private $sharedWith;
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
	/** @var string */
	private $token;
	/** @var int */
	private $parent;
	/** @var string */
	private $target;
	/** @var int */
	private $shareTime;
	/** @var bool */
	private $mailSend;

	/**
	 * Set the id of the share
	 *
	 * @param string $id
	 * @return IShare The modified object
	 */
	public function setId($id) {
		$this->id = $id;
		return $this;
	}

	/**
	 * Get the id of the share
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @inheritdoc
	 */
	public function getFullId() {
		return $this->providerId . ':' . $this->id;
	}

	/**
	 * @inheritdoc
	 */
	public function setProviderId($id) {
		$this->providerId = $id;
		return $this;
	}

	/**
	 * Set the path of this share
	 *
	 * @param Node $path
	 * @return IShare The modified object
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
	 * @return IShare The modified object
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
	 * Set the receiver of this share
	 *
	 * @param IUser|IGroup|string
	 * @return IShare The modified object
	 */
	public function setSharedWith($sharedWith) {
		$this->sharedWith = $sharedWith;
		return $this;
	}

	/**
	 * Get the receiver of this share
	 *
	 * @return IUser|IGroup|string
	 */
	public function getSharedWith() {
		return $this->sharedWith;
	}

	/**
	 * Set the permissions
	 *
	 * @param int $permissions
	 * @return IShare The modified object
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
	 * @return IShare The modified object
	 */
	public function setExpirationDate($expireDate) {
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
	 * @return IShare The modified object
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
	 * @return IShare The modified object
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
	 * @return IShare The modified object
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
	public function getPassword() {
		return $this->password;
	}

	/**
	 * Set the token
	 *
	 * @param string $token
	 * @return IShare The modified object
	 */
	public function setToken($token) {
		$this->token = $token;
		return $this;
	}

	/**
	 * Get the token
	 *
	 * @return string
	 */
	public function getToken() {
		return $this->token;
	}

	/**
	 * Set the parent id of this share
	 *
	 * @param int $parent
	 * @return IShare The modified object
	 */
	public function setParent($parent) {
		$this->parent = $parent;
		return $this;
	}

	/**
	 * Get the parent id of this share
	 *
	 * @return int
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * Set the target of this share
	 *
	 * @param string $target
	 * @return IShare The modified object
	 */
	public function setTarget($target) {
		$this->target = $target;
		return $this;
	}

	/**
	 * Get the target of this share
	 *
	 * @return string
	 */
	public function getTarget() {
		return $this->target;
	}

	/**
	 * Set the time this share was created
	 *
	 * @param int $shareTime
	 * @return IShare The modified object
	 */
	public function setShareTime($shareTime) {
		$this->shareTime = $shareTime;
		return $this;
	}

	/**
	 * Get the timestamp this share was created
	 *
	 * @return int
	 */
	public function getSharetime() {
		return $this->shareTime;
	}

	/**
	 * Set mailSend
	 *
	 * @param bool $mailSend
	 * @return IShare The modified object
	 */
	public function setMailSend($mailSend) {
		$this->mailSend = $mailSend;
		return $this;
	}

	/**
	 * Get mailSend
	 *
	 * @return bool
	 */
	public function getMailSend() {
		return $this->mailSend;
	}
}
