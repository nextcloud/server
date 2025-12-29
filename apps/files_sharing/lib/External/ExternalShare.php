<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\External;

use OC\Files\Filesystem;
use OCA\Files_Sharing\ResponseDefinitions;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;
use OCP\IGroup;
use OCP\IUser;
use OCP\Share\IShare;

/**
 * @method string getId()
 * @method void setId(string $id)
 * @method string getParent()
 * @method void setParent(string $parent)
 * @method int|null getShareType()
 * @method void setShareType(int $shareType)
 * @method string getRemote()
 * @method void setRemote(string $remote)
 * @method string getRemoteId()
 * @method void setRemoteId(string $remoteId)
 * @method string getShareToken()
 * @method void setShareToken(string $shareToken)
 * @method string|null getPassword()
 * @method void setPassword(?string $password)
 * @method string getName()
 * @method string getOwner()
 * @method void setOwner(string $owner)
 * @method string getUser()
 * @method void setUser(string $user)
 * @method string getMountpoint()
 * @method string getMountpointHash()
 * @method void setMountpointHash(string $mountPointHash)
 * @method int getAccepted()
 * @method void setAccepted(int $accepted)
 *
 * @psalm-import-type Files_SharingRemoteShare from ResponseDefinitions
 */
class ExternalShare extends Entity implements \JsonSerializable {
	protected string $parent = '-1';
	protected ?int $shareType = null;
	protected ?string $remote = null;
	protected ?string $remoteId = null;
	protected ?string $shareToken = null;
	protected ?string $password = null;
	protected ?string $name = null;
	protected ?string $owner = null;
	protected ?string $user = null;
	protected ?string $mountpoint = null;
	protected ?string $mountpointHash = null;
	protected ?int $accepted = null;

	public function __construct() {
		$this->addType('id', Types::STRING); // Stored as a bigint
		$this->addType('parent', Types::STRING); // Stored as a bigint
		$this->addType('shareType', Types::INTEGER);
		$this->addType('remote', Types::STRING);
		$this->addType('remoteId', Types::STRING);
		$this->addType('shareToken', Types::STRING);
		$this->addType('password', Types::STRING);
		$this->addType('name', Types::STRING);
		$this->addType('owner', Types::STRING);
		$this->addType('user', Types::STRING);
		$this->addType('mountpoint', Types::STRING);
		$this->addType('mountpointHash', Types::STRING);
		$this->addType('accepted', Types::INTEGER);
	}

	public function setMountpoint(string $mountPoint): void {
		$this->setter('mountpoint', [$mountPoint]);
		$this->setMountpointHash(md5($mountPoint));
	}

	public function setName(string $name): void {
		$name = Filesystem::normalizePath('/' . $name);
		$this->setter('name', [$name]);
	}

	public function setShareWith(IUser|IGroup $shareWith): void {
		$this->setUser($shareWith instanceof IGroup ? $shareWith->getGID() : $shareWith->getUID());
	}

	/**
	 * @return Files_SharingRemoteShare
	 */
	public function jsonSerialize(): array {
		$parent = $this->getParent();
		return [
			'id' => $this->getId(),
			'parent' => $parent,
			'share_type' => $this->getShareType() ?? IShare::TYPE_USER, // unfortunately nullable on the DB level, but never null.
			'remote' => $this->getRemote(),
			'remote_id' => $this->getRemoteId(),
			'share_token' => $this->getShareToken(),
			'name' => $this->getName(),
			'owner' => $this->getOwner(),
			'user' => $this->getUser(),
			'mountpoint' => $this->getMountpoint(),
			'accepted' => $this->getAccepted(),

			// Added later on
			'file_id' => null,
			'mimetype' => null,
			'permissions' => null,
			'mtime' => null,
			'type' => null,
		];
	}

	/**
	 * @internal For unit tests
	 * @return ExternalShare
	 */
	public function clone(): self {
		$newShare = new ExternalShare();
		$newShare->setParent($this->getParent());
		$newShare->setShareType($this->getShareType());
		$newShare->setRemote($this->getRemote());
		$newShare->setRemoteId($this->getRemoteId());
		$newShare->setShareToken($this->getShareToken());
		$newShare->setPassword($this->getPassword());
		$newShare->setName($this->getName());
		$newShare->setOwner($this->getOwner());
		$newShare->setMountpoint($this->getMountpoint());
		$newShare->setAccepted($this->getAccepted());
		$newShare->setPassword($this->getPassword());
		return $newShare;
	}
}
