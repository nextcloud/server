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


use daita\NcSmallPhpTools\Model\Options;
use daita\NcSmallPhpTools\Traits\TArrayTools;
use daita\NcSmallPhpTools\Traits\TStringTools;
use DateTime;
use Exception;
use JsonSerializable;
use OC;
use OCP\Entities\Model\IEntityAccount;
use OCP\Entities\Model\IEntityMember;


/**
 * Class EntityAccount
 *
 * @package OC\Entities\Model
 */
class EntityAccount implements IEntityAccount, JsonSerializable {


	use TArrayTools;
	use TStringTools;


	/** @var string */
	private $id = '';

	/** @var string */
	private $type = '';

	/** @var string */
	private $account = '';

	/** @var int */
	private $deleteOn = 0;

	/** @var int */
	private $creation = 0;

	/** @var Options */
	private $options;

	/**
	 * EntityAccount constructor.
	 *
	 * @param string $id
	 */
	public function __construct(string $id = '') {
		$this->options = new Options();
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
	 * @return IEntityAccount
	 */
	public function setId(string $id): IEntityAccount {
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
	 * @return IEntityAccount
	 */
	public function setType(string $type): IEntityAccount {
		$this->type = $type;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getAccount(): string {
		return $this->account;
	}

	/**
	 * @param string $account
	 *
	 * @return IEntityAccount
	 */
	public function setAccount(string $account): IEntityAccount {
		$this->account = $account;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getDeleteOn(): int {
		return $this->deleteOn;
	}

	/**
	 * @param int $deleteOn
	 *
	 * @return IEntityAccount
	 */
	public function setDeleteOn(int $deleteOn): IEntityAccount {
		$this->deleteOn = $deleteOn;

		return $this;
	}

	/**
	 * @param string $in
	 *
	 * @return IEntityAccount
	 * @throws Exception
	 */
	public function setDeleteIn(string $in): IEntityAccount {
		$dTime = new DateTime($in);

		$this->setDeleteOn($dTime->getTimestamp());

		return $this;
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
	 * @return IEntityAccount
	 */
	public function setCreation(int $creation): IEntityAccount {
		$this->creation = $creation;

		return $this;
	}


	public function getOptions(): Options {
		return $this->options;
	}


	/**
	 * @param array $data
	 *
	 * @return IEntityAccount
	 */
	public function importFromDatabase(array $data): IEntityAccount {
		$this->setId($this->get('id', $data, ''));
		$this->setType($this->get('type', $data, ''));
		$this->setAccount($this->get('account', $data, ''));
		$this->setDeleteOn($this->getInt('delete_on', $data, 0));

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
			'id'       => $this->getId(),
			'type'     => $this->getType(),
			'account'  => $this->getAccount(),
			'creation' => $this->getCreation()
		];
	}


	/**
	 * @return IEntityMember[]
	 */
	public function belongsTo(): array {
		return OC::$server->getEntitiesManager()
						  ->accountBelongsTo($this);
	}


	public function hasAdminRights(): bool {
		return OC::$server->getEntitiesManager()
						  ->accountHasAdminRights($this);
	}


	/**
	 * @param string $entityId
	 */
	public function inviteTo(string $entityId): void {
		OC::$server->getEntitiesManager()
				   ->accountInviteTo($this, $entityId);
	}

}

