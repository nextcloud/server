<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Class AddressBookDeletedEvent
 *
 * @package OCA\DAV\Events
 * @since 20.0.0
 */
class AddressBookDeletedEvent extends Event {

	/** @var int */
	private $addressBookId;

	/** @var array */
	private $addressBookData;

	/** @var array */
	private $shares;

	/**
	 * AddressBookDeletedEvent constructor.
	 *
	 * @param int $addressBookId
	 * @param array $addressBookData
	 * @param array $shares
	 * @since 20.0.0
	 */
	public function __construct(int $addressBookId,
		array $addressBookData,
		array $shares) {
		parent::__construct();
		$this->addressBookId = $addressBookId;
		$this->addressBookData = $addressBookData;
		$this->shares = $shares;
	}

	/**
	 * @return int
	 * @since 20.0.0
	 */
	public function getAddressBookId():int {
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
}
