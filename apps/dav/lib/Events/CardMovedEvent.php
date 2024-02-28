<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Class CardMovedEvent
 *
 * @package OCA\DAV\Events
 * @since 27.0.0
 */
class CardMovedEvent extends Event {
	private int $sourceAddressBookId;
	private array $sourceAddressBookData;
	private int $targetAddressBookId;
	private array $targetAddressBookData;
	private array $sourceShares;
	private array $targetShares;
	private array $objectData;

	/**
	 * @since 27.0.0
	 */
	public function __construct(int $sourceAddressBookId,
		array $sourceAddressBookData,
		int $targetAddressBookId,
		array $targetAddressBookData,
		array $sourceShares,
		array $targetShares,
		array $objectData) {
		parent::__construct();
		$this->sourceAddressBookId = $sourceAddressBookId;
		$this->sourceAddressBookData = $sourceAddressBookData;
		$this->targetAddressBookId = $targetAddressBookId;
		$this->targetAddressBookData = $targetAddressBookData;
		$this->sourceShares = $sourceShares;
		$this->targetShares = $targetShares;
		$this->objectData = $objectData;
	}

	/**
	 * @return int
	 * @since 27.0.0
	 */
	public function getSourceAddressBookId(): int {
		return $this->sourceAddressBookId;
	}

	/**
	 * @return array
	 * @since 27.0.0
	 */
	public function getSourceAddressBookData(): array {
		return $this->sourceAddressBookData;
	}

	/**
	 * @return int
	 * @since 27.0.0
	 */
	public function getTargetAddressBookId(): int {
		return $this->targetAddressBookId;
	}

	/**
	 * @return array
	 * @since 27.0.0
	 */
	public function getTargetAddressBookData(): array {
		return $this->targetAddressBookData;
	}

	/**
	 * @return array
	 * @since 27.0.0
	 */
	public function getSourceShares(): array {
		return $this->sourceShares;
	}

	/**
	 * @return array
	 * @since 27.0.0
	 */
	public function getTargetShares(): array {
		return $this->targetShares;
	}

	/**
	 * @return array
	 * @since 27.0.0
	 */
	public function getObjectData(): array {
		return $this->objectData;
	}
}
