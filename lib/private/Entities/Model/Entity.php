<?php
declare(strict_types=1);


/**
 * Entities - Entity & Groups of Entities
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019, Maxence Lange <maxence@artificial-owl.com>
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC\Entities\Model;


use daita\NcSmallPhpTools\Traits\TArrayTools;
use daita\NcSmallPhpTools\Traits\TStringTools;
use DateTime;
use Exception;
use JsonSerializable;
use OC;
use OC\Entities\Exceptions\EntityMemberNotFoundException;
use OCP\Entities\Model\IEntity;
use OCP\Entities\Model\IEntityAccount;
use OCP\Entities\Model\IEntityMember;


/**
 * Class Entity
 *
 * @package OC\Entities\Model
 */
class Entity implements IEntity, JsonSerializable {


	use TArrayTools;
	use TStringTools;


	/** @var string */
	private $id = '';

	/** @var string */
	private $type = '';

	/** @var string */
	private $ownerId = '';

	/** @var IEntityAccount */
	private $owner;

	/** @var IEntityMember */
	private $viewer;

	/** @var int */
	private $visibility = 0;

	/** @var int */
	private $access = 0;

	/** @var string */
	private $name = '';

	/** @var int */
	private $creation = 0;

	/** @var IEntityMember[] */
	private $members = [];


	/**
	 * Entity constructor.
	 *
	 * @param string $id
	 */
	public function __construct(string $id = '') {
		$this->id = $id;

		if ($this->id === '') {
			$this->id = $this->uuid(11);
		}
	}


	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @param string $id
	 *
	 * @return IEntity
	 */
	public function setId(string $id): IEntity {
		$this->id = $id;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @param string $type
	 *
	 * @return IEntity
	 */
	public function setType(string $type): IEntity {
		$this->type = $type;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getOwnerId(): string {
		if ($this->hasOwner()) {
			try {
				return $this->getOwner()
							->getId();
			} catch (EntityMemberNotFoundException $e) {
			}
		}

		return $this->ownerId;
	}

	/**
	 * @param string $ownerId
	 *
	 * @return IEntity
	 */
	public function setOwnerId(string $ownerId): IEntity {
		$this->ownerId = $ownerId;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getVisibility(): int {
		return $this->visibility;
	}

	/**
	 * @param int $visibility
	 *
	 * @return IEntity
	 */
	public function setVisibility(int $visibility): IEntity {
		$this->visibility = $visibility;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getVisibilityString(): string {
		return $this->get((string)$this->visibility, IEntity::CONVERT_VISIBILITY, '');
	}


	/**
	 * @return int
	 */
	public function getAccess(): int {
		return $this->access;
	}

	/**
	 * @param int $access
	 *
	 * @return IEntity
	 */
	public function setAccess(int $access): IEntity {
		$this->access = $access;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAccessString(): string {
		return $this->get((string)$this->access, IEntity::CONVERT_ACCESS, '');
	}


	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @param string $name
	 *
	 * @return IEntity
	 */
	public function setName(string $name): IEntity {
		$this->name = $name;

		return $this;
	}

//
//	/**
//	 * @return IEntityMember[]
//	 */
//	public function getMembers(): array {
//		return $this->members;
//	}
//
//	/**
//	 * @param IEntityMember[] $members
//	 *
//	 * @return IEntity
//	 */
//	public function setMembers(array $members): IEntity {
//		$this->members = $members;
//
//		return $this;
//	}
//
//	/**
//	 * @param IEntityMember[] $members
//	 *
//	 * @return IEntity
//	 */
//	public function addMembers(array $members): IEntity {
//		$this->members = array_merge($this->members, $members);
//
//		return $this;
//	}
//
//	/**
//	 * @param IEntityMember $member
//	 *
//	 * @return IEntity
//	 */
//	public function addMember(IEntityMember $member): IEntity {
//		$this->members[] = $member;
//
//		return $this;
//	}


	/**
	 * @return int
	 */
	public function getCreation(): int {
		return $this->creation;
	}

	/**
	 * @param int $creation
	 *
	 * @return IEntity
	 */
	public function setCreation(int $creation): IEntity {
		$this->creation = $creation;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function hasOwner(): bool {
		return ($this->owner !== null);
	}

	/**
	 * @return IEntityAccount
	 * @throws EntityMemberNotFoundException
	 */
	public function getOwner(): IEntityAccount {
		if ($this->owner !== null) {
			return $this->owner;
		}

		// TODO: return IEntityAccount instead of IEntityMember
//		foreach ($this->getMembers() as $member) {
//			if ($member->getLevel() === IEntityMember::LEVEL_OWNER) {
//				return $member;
//			}
//		}

		throw new EntityMemberNotFoundException('Cannot find Owner');
	}

	public function setOwner(IEntityAccount $owner): IEntity {
		$this->owner = $owner;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function hasViewer(): bool {
		return ($this->viewer !== null);
	}

	/**
	 * @return IEntityMember
	 * @throws EntityMemberNotFoundException
	 */
	public function getViewer(): IEntityMember {
		if ($this->viewer !== null) {
			return $this->viewer;
		}

		throw new EntityMemberNotFoundException('Cannot find Viewer');
	}

	public function setViewer(IEntityMember $viewer): IEntity {
		$this->viewer = $viewer;

		return $this;
	}


	/**
	 * @param array $data
	 *
	 * @return IEntity
	 */
	public function importFromDatabase(array $data): IEntity {
		$this->setId($this->get('id', $data, ''));
		$this->setType($this->get('type', $data, ''));
		$this->setOwnerId($this->get('owner_id', $data, ''));
		$this->setVisibility($this->getInt('visibility', $data, 0));
		$this->setAccess($this->getInt('access', $data, 0));
		$this->setName($this->get('name', $data, ''));

		try {
			$creation = new DateTime($this->get('creation', $data, ''));
			$this->setCreation($creation->getTimestamp());
		} catch (Exception $e) {
		}

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id'         => $this->getId(),
			'type'       => $this->getType(),
			'owner_id'   => $this->getOwnerId(),
			'visibility' => $this->getVisibility(),
			'access'     => $this->getAccess(),
			'name'       => $this->getName(),
			'creation'   => $this->getCreation()
		];
	}

	/**
	 * @return IEntity[]
	 */
	public function belongsTo(): array {
		return OC::$server->getEntitiesManager()
						  ->entityBelongsTo($this);
	}


	/**
	 * @return IEntityMember[]
	 */
	public function getMembers(): array {
		return OC::$server->getEntitiesManager()
						  ->entityGetMembers($this);
	}


	public function pointOfView(): IEntityMember {
		return OC::$server->getEntitiesManager()
						  ->entityPointOfView($this);
	}


	/**
	 * @return bool
	 */
	public function hasAdminRights(): bool {
		return OC::$server->getEntitiesManager()
						  ->entityHasAdminRights($this);
	}

}

