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
use JsonSerializable;
use OCP\Entities\Model\IEntityType;
use stdClass;


/**
 * Class EntityAccount
 *
 * @package OC\Entities\Model
 */
class EntityType implements IEntityType, JsonSerializable {


	use TArrayTools;
	use TStringTools;


	/** @var int */
	private $id = 0;

	/** @var string */
	private $type = '';

	/** @var string */
	private $interface = '';

	/** @var string */
	private $className = '';

	/** @var stdClass */
	private $class = null;


	/**
	 * EntityType constructor.
	 *
	 * @param string $interface
	 * @param string $type
	 * @param string $className
	 */
	public function __construct(string $interface = '', string $type = '', string $className = '') {
		$this->interface = $interface;
		$this->type = $type;
		$this->className = $className;
	}


	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @param int $id
	 *
	 * @return IEntityType
	 */
	public function setId(int $id): IEntityType {
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
	 * @return IEntityType
	 */
	public function setType(string $type): IEntityType {
		$this->type = $type;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getInterface(): string {
		return $this->interface;
	}

	/**
	 * @param string $interface
	 *
	 * @return IEntityType
	 */
	public function setInterface(string $interface): IEntityType {
		$this->interface = $interface;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getClassName(): string {
		return $this->className;
	}

	/**
	 * @param string $className
	 *
	 * @return IEntityType
	 */
	public function setClassName(string $className): IEntityType {
		$this->className = $className;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function hasClass(): bool {
		return ($this->class !== null);
	}

	/**
	 * @return stdClass
	 */
	public function getClass() {
		return $this->class;
	}

	/**
	 * @param stdClass $class
	 */
	public function setClass($class): void {
		$this->class = $class;
	}


	/**
	 * @param array $data
	 *
	 * @return IEntityType
	 */
	public function importFromDatabase(array $data): IEntityType {
		$this->setId($this->getInt('id', $data, 0));
		$this->setType($this->get('type', $data, ''));
		$this->setInterface($this->get('interface', $data, ''));
		$this->setClassName($this->get('class', $data, ''));

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id'        => $this->getId(),
			'type'      => $this->getType(),
			'interface' => $this->getInterface(),
			'class'     => $this->getClassName()
		];
	}

}

