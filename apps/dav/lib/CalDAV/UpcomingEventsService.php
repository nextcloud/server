<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\IManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use function array_map;

class UpcomingEventsService {
	public function __construct(
		private IManager $calendarManager,
		private ITimeFactory $timeFactory,
		private IUserManager $userManager,
		private IAppManager $appManager,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @return UpcomingEvent[]
	 */
	public function getEvents(string $userId, ?string $location = null): array {
		$searchQuery = $this->calendarManager->newQuery('principals/users/' . $userId);
		if ($location !== null) {
			$searchQuery->addSearchProperty('LOCATION');
			$searchQuery->setSearchPattern($location);
		}
		$searchQuery->addType('VEVENT');
		$searchQuery->setLimit(3);
		$now = $this->timeFactory->now();
		$searchQuery->setTimerangeStart($now->modify('-1 minute'));
		$searchQuery->setTimerangeEnd($now->modify('+1 month'));

		$events = $this->calendarManager->searchForPrincipal($searchQuery);
		$calendarAppEnabled = $this->appManager->isEnabledForUser(
			'calendar',
			$this->userManager->get($userId),
		);

		return array_map(function (array $event) use ($userId, $calendarAppEnabled) {
			$calendarAppUrl = null;

			if ($calendarAppEnabled) {
				$arguments = [
					'objectId' => base64_encode($this->urlGenerator->getWebroot() . '/remote.php/dav/calendars/' . $userId . '/' . $event['calendar-uri'] . '/' . $event['uri']),
				];

				if (isset($event['RECURRENCE-ID'])) {
					$arguments['recurrenceId'] = $event['RECURRENCE-ID'][0];
				}
				/**
				 * TODO: create a named, deep route in calendar (it's a code smell to just assume this route exists, find an abstraction)
				 * When changing, also adjust for:
				 * - spreed/lib/Service/CalendarIntegrationService.php#getDashboardEvents
				 * - spreed/lib/Service/CalendarIntegrationService.php#getMutualEvents
				 */
				$calendarAppUrl = $this->urlGenerator->linkToRouteAbsolute('calendar.view.indexdirect.edit', $arguments);
			}

			return new UpcomingEvent(
				$event['uri'],
				($event['objects'][0]['RECURRENCE-ID'][0] ?? null)?->getTimeStamp(),
				$event['calendar-uri'],
				$event['objects'][0]['DTSTART'][0]?->getTimestamp(),
				$event['objects'][0]['SUMMARY'][0] ?? null,
				$event['objects'][0]['LOCATION'][0] ?? null,
				$calendarAppUrl,
			);
		}, $events);
	}

}
