<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Class CalendarPublishedEvent
 *
 * @package OCA\DAV\Events
 * @since 20.0.0
 */
class CalendarPublishedEvent extends Event {
	private int $calendarId;
	private array $calendarData;
	private string $publicUri;

	/**
	 * CalendarPublishedEvent constructor.
	 *
	 * @param int $calendarId
	 * @param array $calendarData
	 * @param string $publicUri
	 * @since 20.0.0
	 */
	public function __construct(int $calendarId,
		array $calendarData,
		string $publicUri) {
		parent::__construct();
		$this->calendarId = $calendarId;
		$this->calendarData = $calendarData;
		$this->publicUri = $publicUri;
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
	 * @return string
	 * @since 20.0.0
	 */
	public function getPublicUri(): string {
		return $this->publicUri;
	}
}
