<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\EventDispatcher\Event;

/**
 * Class CalendarShareUpdatedEvent
 *
 * @package OCA\DAV\Events
 * @since 20.0.0
 *
 * @psalm-import-type CalendarInfo from CalDavBackend
 */
class CalendarShareUpdatedEvent extends Event {
	/**
	 * CalendarShareUpdatedEvent constructor.
	 *
	 * @param int $calendarId
	 * @psalm-param CalendarInfo $calendarData
	 * @param array $calendarData
	 * @param list<array{href: string, commonName: string, status: int, readOnly: bool, '{http://owncloud.org/ns}principal': string, '{http://owncloud.org/ns}group-share': bool}> $oldShares
	 * @param list<array{href: string, commonName: string, readOnly: bool}> $added
	 * @param list<string> $removed
	 * @since 20.0.0
	 */
	public function __construct(
		private int $calendarId,
		private array $calendarData,
		private array $oldShares,
		private array $added,
		private array $removed,
	) {
		parent::__construct();
	}

	/**
	 * @since 20.0.0
	 */
	public function getCalendarId(): int {
		return $this->calendarId;
	}

	/**
	 * @psalm-return CalendarInfo
	 * @return array
	 * @since 20.0.0
	 */
	public function getCalendarData(): array {
		return $this->calendarData;
	}

	/**
	 * @return list<array{href: string, commonName: string, status: int, readOnly: bool, '{http://owncloud.org/ns}principal': string, '{http://owncloud.org/ns}group-share': bool}>
	 * @since 20.0.0
	 */
	public function getOldShares(): array {
		return $this->oldShares;
	}

	/**
	 * @return list<array{href: string, commonName: string, readOnly: bool}>
	 * @since 20.0.0
	 */
	public function getAdded(): array {
		return $this->added;
	}

	/**
	 * @return list<string>
	 * @since 20.0.0
	 */
	public function getRemoved(): array {
		return $this->removed;
	}
}
