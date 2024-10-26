<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	/**
	 * @since 27.0.0
	 */
	public function __construct(
		private int $sourceAddressBookId,
		private array $sourceAddressBookData,
		private int $targetAddressBookId,
		private array $targetAddressBookData,
		private array $sourceShares,
		private array $targetShares,
		private array $objectData,
	) {
		parent::__construct();
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
