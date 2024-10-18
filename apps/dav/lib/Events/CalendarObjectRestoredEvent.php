<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * @since 22.0.0
 */
class CalendarObjectRestoredEvent extends Event {

	/**
	 * @param int $calendarId
	 * @param array $calendarData
	 * @param array $shares
	 * @param array $objectData
	 * @since 22.0.0
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
	 * @since 22.0.0
	 */
	public function getCalendarId(): int {
		return $this->calendarId;
	}

	/**
	 * @return array
	 * @since 22.0.0
	 */
	public function getCalendarData(): array {
		return $this->calendarData;
	}

	/**
	 * @return array
	 * @since 22.0.0
	 */
	public function getShares(): array {
		return $this->shares;
	}

	/**
	 * @return array
	 * @since 22.0.0
	 */
	public function getObjectData(): array {
		return $this->objectData;
	}
}
