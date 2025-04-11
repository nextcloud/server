<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Class AddressBookUpdatedEvent
 *
 * @package OCA\DAV\Events
 * @since 20.0.0
 */
class AddressBookUpdatedEvent extends Event {

	/**
	 * AddressBookUpdatedEvent constructor.
	 *
	 * @param int $addressBookId
	 * @param array $addressBookData
	 * @param array $shares
	 * @param array $mutations
	 * @since 20.0.0
	 */
	public function __construct(
		private int $addressBookId,
		private array $addressBookData,
		private array $shares,
		private array $mutations,
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
	public function getShares(): array {
		return $this->shares;
	}

	/**
	 * @return array
	 * @since 20.0.0
	 */
	public function getMutations(): array {
		return $this->mutations;
	}
}
