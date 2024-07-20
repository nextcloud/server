<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Class CalendarCreatedEvent
 *
 * @package OCA\DAV\Events
 * @since 20.0.0
 */
class CalendarCreatedEvent extends Event {

	/** @var int */
	private $calendarId;

	/** @var array */
	private $calendarData;

	/**
	 * CalendarCreatedEvent constructor.
	 *
	 * @param int $calendarId
	 * @param array $calendarData
	 * @since 20.0.0
	 */
	public function __construct(int $calendarId,
		array $calendarData) {
		parent::__construct();
		$this->calendarId = $calendarId;
		$this->calendarData = $calendarData;
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
}
