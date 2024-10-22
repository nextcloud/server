<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Class AddressBookCreatedEvent
 *
 * @package OCA\DAV\Events
 * @since 20.0.0
 */
class AddressBookCreatedEvent extends Event {

	/**
	 * AddressBookCreatedEvent constructor.
	 *
	 * @param int $addressBookId
	 * @param array $addressBookData
	 * @since 20.0.0
	 */
	public function __construct(
		private int $addressBookId,
		private array $addressBookData,
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
}
