<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;

/**
 * Class CalendarShareUpdatedEvent
 *
 * @package OCA\DAV\Events
 * @since 20.0.0
 */
class CalendarShareUpdatedEvent extends Event {
	private int $calendarId;

	/** @var array{id: int, uri: string, '{http://calendarserver.org/ns/}getctag': string, '{http://sabredav.org/ns}sync-token': int, '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set': SupportedCalendarComponentSet, '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp': ScheduleCalendarTransp } */
	private array $calendarData;

	/** @var list<array{href: string, commonName: string, status: int, readOnly: bool, '{http://owncloud.org/ns}principal': string, '{http://owncloud.org/ns}group-share': bool}> */
	private array $oldShares;

	/** @var list<array{href: string, commonName: string, readOnly: bool}> */
	private array $added;

	/** @var list<string> */
	private array $removed;

	/**
	 * CalendarShareUpdatedEvent constructor.
	 *
	 * @param int $calendarId
	 * @param array{id: int, uri: string, '{http://calendarserver.org/ns/}getctag': string, '{http://sabredav.org/ns}sync-token': int, '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set': SupportedCalendarComponentSet, '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp': ScheduleCalendarTransp } $calendarData
	 * @param list<array{href: string, commonName: string, status: int, readOnly: bool, '{http://owncloud.org/ns}principal': string, '{http://owncloud.org/ns}group-share': bool}> $oldShares
	 * @param list<array{href: string, commonName: string, readOnly: bool}> $added
	 * @param list<string> $removed
	 * @since 20.0.0
	 */
	public function __construct(int $calendarId,
								array $calendarData,
								array $oldShares,
								array $added,
								array $removed) {
		parent::__construct();
		$this->calendarId = $calendarId;
		$this->calendarData = $calendarData;
		$this->oldShares = $oldShares;
		$this->added = $added;
		$this->removed = $removed;
	}

	/**
	 * @since 20.0.0
	 */
	public function getCalendarId(): int {
		return $this->calendarId;
	}

	/**
	 * @return array{id: int, uri: string, '{http://calendarserver.org/ns/}getctag': string, '{http://sabredav.org/ns}sync-token': int, '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set': SupportedCalendarComponentSet, '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp': ScheduleCalendarTransp }
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
