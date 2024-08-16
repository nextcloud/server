<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Class CardUpdatedEvent
 *
 * @package OCA\DAV\Events
 * @since 20.0.0
 */
class CardUpdatedEvent extends Event {

	/** @var int */
	private $addressBookId;

	/** @var array */
	private $addressBookData;

	/** @var array */
	private $shares;

	/** @var array */
	private $cardData;

	/**
	 * CardUpdatedEvent constructor.
	 *
	 * @param int $addressBookId
	 * @param array $addressBookData
	 * @param array $shares
	 * @param array $cardData
	 * @since 20.0.0
	 */
	public function __construct(int $addressBookId,
		array $addressBookData,
		array $shares,
		array $cardData) {
		parent::__construct();
		$this->addressBookId = $addressBookId;
		$this->addressBookData = $addressBookData;
		$this->shares = $shares;
		$this->cardData = $cardData;
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
	public function getCardData(): array {
		return $this->cardData;
	}
}
