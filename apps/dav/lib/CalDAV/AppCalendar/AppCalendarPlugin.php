<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\AppCalendar;

use OCA\DAV\CalDAV\CachedSubscriptionImpl;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\CalDAV\Federation\FederatedCalendarImpl;
use OCA\DAV\CalDAV\Integration\ExternalCalendar;
use OCA\DAV\CalDAV\Integration\ICalendarProvider;
use OCP\Calendar\IManager;
use Psr\Log\LoggerInterface;

/* Plugin for wrapping application generated calendars registered in nextcloud core (OCP\Calendar\ICalendarProvider) */
class AppCalendarPlugin implements ICalendarProvider {
	public function __construct(
		protected IManager $manager,
		protected LoggerInterface $logger,
	) {
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
				return !(
					($c instanceof CalendarImpl)
					|| ($c instanceof CachedSubscriptionImpl)
					|| ($c instanceof FederatedCalendarImpl)
				);
			})
		);
	}
}
