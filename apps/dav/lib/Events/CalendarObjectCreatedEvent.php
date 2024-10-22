<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Class CalendarObjectCreatedEvent
 *
 * @package OCA\DAV\Events
 * @since 20.0.0
 */
class CalendarObjectCreatedEvent extends Event {

	/**
	 * CalendarObjectCreatedEvent constructor.
	 *
	 * @param int $calendarId
	 * @param array $calendarData
	 * @param array $shares
	 * @param array $objectData
	 * @since 20.0.0
	 */
	public function __construct(
		private int $calendarId,
		private array $calendarData,
		private array $shares,
		private array $objectData,
	) {
		parent::__construct();
	}

	/**
	 * @return int
	 * @since 20.0.0
	 */
	public function getCalendarId(): int {
		return $this->calendarId;
	}

	/**
	 * @return array
	 * @since 20.0.0
	 */
	public function getCalendarData(): array {
		return $this->calendarData;
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
	public function getObjectData(): array {
		return $this->objectData;
	}
}
