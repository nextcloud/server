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
use OC\Entities\Exceptions\EntityAccountNotFoundException;
use OC\Entities\Exceptions\EntityNotFoundException;
use OCP\Entities\Model\IEntity;
use OCP\Entities\Model\IEntityAccount;
use OCP\Entities\Model\IEntityMember;


/**
 * Class EntityMember
 *
 * @package OC\Entities\Model
 */
class EntityMember implements IEntityMember, JsonSerializable {


	use TArrayTools;
	use TStringTools;


	/** @var string */
	private $id = '';

	/** @var string */
	private $entityId = '';

	/** @var IEntity */
	private $entity = null;

	/** @var string */
	private $accountId = '';

	/** @var IEntityAccount */
	private $account = null;

	/** @var string */
	private $slaveEntityId = '';

	/** @var string */
	private $status = '';

	/** @var int */
	private $level = 0;

	/** @var int */
	private $creation = 0;


	/**
	 * EntityMember constructor.
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
	 * @return IEntityMember
	 */
	public function setId(string $id): IEntityMember {
		$this->id = $id;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getEntityId(): string {
		try {
			return $this->getEntity()
						->getId();
		} catch (EntityNotFoundException $e) {
		}

		return $this->entityId;
	}

	/**
	 * @param string $entityId
	 *
	 * @return IEntityMember
	 */
	public function setEntityId(string $entityId): IEntityMember {
		$this->entityId = $entityId;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function hasEntity(): bool {
		return ($this->entity !== null);
	}

	/**
	 * @return IEntity
	 * @throws EntityNotFoundException
	 */
	public function getEntity(): IEntity {
		if ($this->entity !== null) {
			return $this->entity;
		}

		throw new EntityNotFoundException();
	}

	/**
	 * @param IEntity $entity
	 *
	 * @return IEntityMember
	 */
	public function setEntity(IEntity $entity): IEntityMember {
		$this->entity = $entity;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function hasAccount(): bool {
		return ($this->account !== null);
	}

	/**
	 * @return IEntityAccount
	 * @throws EntityAccountNotFoundException
	 */
	public function getAccount(): IEntityAccount {
		if ($this->account !== null) {
			return $this->account;
		}

		throw new EntityAccountNotFoundException();
	}

	/**
	 * @param IEntityAccount $account
	 */
	public function setAccount(IEntityAccount $account) {
		$this->account = $account;
	}


	/**
	 * @return string
	 */
	public function getAccountId(): string {
		try {
			return $this->getAccount()
						->getId();
		} catch (EntityAccountNotFoundException $e) {
		}

		return $this->accountId;
	}

	/**
	 * @param string $accountId
	 *
	 * @return IEntityMember
	 */
	public function setAccountId(string $accountId): IEntityMember {
		$this->accountId = $accountId;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getSlaveEntityId(): string {
		return $this->slaveEntityId;
	}

	/**
	 * @param string $slaveEntityId
	 *
	 * @return IEntityMember
	 */
	public function setSlaveEntityId(string $slaveEntityId): IEntityMember {
		$this->slaveEntityId = $slaveEntityId;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getStatus(): string {
		return $this->status;
	}

	/**
	 * @param string $status
	 *
	 * @return IEntityMember
	 */
	public function setStatus(string $status): IEntityMember {
		$this->status = $status;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getLevel(): int {
		return $this->level;
	}

	/**
	 * @param int $level
	 *
	 * @return IEntityMember
	 */
	public function setLevel(int $level): IEntityMember {
		$this->level = $level;

		return $this;
	}

	public function getLevelString(): string {
		return $this->get((string) $this->level, IEntityMember::CONVERT_LEVEL, '');
	}


	/**
	 * @return int
	 */
	public function getCreation(): int {
		return $this->creation;
	}

	/**
	 * @param int $creation
	 *
	 * @return IEntityMember
	 */
	public function setCreation(int $creation): IEntityMember {
		$this->creation = $creation;

		return $this;
	}


	/**
	 * @param array $data
	 *
	 * @return IEntityMember
	 */
	public function importFromDatabase(array $data): IEntityMember {
		$this->setId($this->get('id', $data, ''));
		$this->setEntityId($this->get('entity_id', $data, ''));
		$this->setAccountId($this->get('account_id', $data, ''));
		$this->setSlaveEntityId($this->get('slave_entity_id', $data, ''));
		$this->setStatus($this->get('status', $data, ''));
		$this->setLevel($this->getInt('level', $data, 0));

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
			'id'              => $this->getId(),
			'entity_id'       => $this->getEntityId(),
			'account_id'      => $this->getAccountId(),
			'slave_entity_id' => $this->getSlaveEntityId(),
			'type'            => $this->getStatus(),
			'level'           => $this->getLevel(),
			'account'         => $this->account,
			'creation'        => $this->getCreation()
		];
	}

}

