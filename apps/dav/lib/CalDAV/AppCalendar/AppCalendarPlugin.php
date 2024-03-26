<?php

declare(strict_types=1);

/**
 * @copyright 2023 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
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

namespace OCA\DAV\CalDAV\AppCalendar;

use OCA\DAV\CalDAV\Integration\ExternalCalendar;
use OCA\DAV\CalDAV\Integration\ICalendarProvider;
use OCP\Calendar\IManager;
use Psr\Log\LoggerInterface;

/* Plugin for wrapping application generated calendars registered in nextcloud core (OCP\Calendar\ICalendarProvider) */
class AppCalendarPlugin implements ICalendarProvider {
	protected IManager $manager;
	protected LoggerInterface $logger;

	public function __construct(IManager $manager, LoggerInterface $logger) {
		$this->manager = $manager;
		$this->logger = $logger;
	}

	public function getAppID(): string {
		return 'dav-wrapper';
	}

	public function fetchAllForCalendarHome(string $principalUri): array {
		return array_map(function ($calendar) use (&$principalUri) {
			return new AppCalendar($this->getAppID(), $calendar, $principalUri);
		}, $this->getWrappedCalendars($principalUri));
	}

	public function hasCalendarInCalendarHome(string $principalUri, string $calendarUri): bool {
		return count($this->getWrappedCalendars($principalUri, [ $calendarUri ])) > 0;
	}

	public function getCalendarInCalendarHome(string $principalUri, string $calendarUri): ?ExternalCalendar {
		$calendars = $this->getWrappedCalendars($principalUri, [ $calendarUri ]);
		if (count($calendars) > 0) {
			return new AppCalendar($this->getAppID(), $calendars[0], $principalUri);
		}

		return null;
	}

	protected function getWrappedCalendars(string $principalUri, array $calendarUris = []): array {
		return array_values(
			array_filter($this->manager->getCalendarsForPrincipal($principalUri, $calendarUris), function ($c) {
				// We must not provide a wrapper for DAV calendars
				return ! (($c instanceof \OCA\DAV\CalDAV\CalendarImpl) || ($c instanceof \OCA\DAV\CalDAV\CachedSubscriptionImpl));
			})
		);
	}
}
