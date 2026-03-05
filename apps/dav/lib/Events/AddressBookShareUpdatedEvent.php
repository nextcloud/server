<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Class AddressBookShareUpdatedEvent
 *
 * @package OCA\DAV\Events
 * @since 20.0.0
 */
class AddressBookShareUpdatedEvent extends Event {

	/**
	 * AddressBookShareUpdatedEvent constructor.
	 *
	 * @param int $addressBookId
	 * @param array $addressBookData
	 * @param array $oldShares
	 * @param array $added
	 * @param array $removed
	 * @since 20.0.0
	 */
	public function __construct(
		private int $addressBookId,
		private array $addressBookData,
		private array $oldShares,
		private array $added,
		private array $removed,
	) {
		parent::__construct();
	}

	/**
	 * @return int
	 * @since 20.0.0
	 */
	public function getAddressBookId(): int {
		return $this->addressBookId;
	}

	/**
	 * @return array
	 * @since 20.0.0
	 */
	public function getAddressBookData(): array {
		return $this->addressBookData;
	}

	/**
	 * @return array
	 * @since 20.0.0
	 */
	public function getOldShares(): array {
		return $this->oldShares;
	}

	/**
	 * @return array
	 * @since 20.0.0
	 */
	public function getAdded(): array {
		return $this->added;
	}

	/**
	 * @return array
	 * @since 20.0.0
	 */
	public function getRemoved(): array {
		return $this->removed;
	}
}
