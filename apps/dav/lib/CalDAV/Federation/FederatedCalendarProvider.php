<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCP\Calendar\ICalendarProvider;

class FederatedCalendarProvider implements ICalendarProvider {
	public function __construct(
		private readonly FederatedCalendarMapper $federatedCalendarsMapper,
	) {
	}

	public function getCalendars(string $principalUri, array $calendarUris = []): array {
		$entities = $this->federatedCalendarsMapper->findByPrincipalUri($principalUri);
		$calendars = array_map(static fn ($entity) => $entity->toFederatedCalendar(), $entities);
		if (!empty($calendarUris)) {
			$calendars = array_filter(
				$calendars,
				static fn(FederatedCalendarImpl $calendar) => in_array(
					$calendar->getUri(),
					$calendarUris,
					true,
				),
			);
		}

		return $calendars;
	}
}
