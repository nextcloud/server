<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use OCP\Calendar\ICalendarProvider;

class CachedSubscriptionProvider implements ICalendarProvider {

	public function __construct(
		private CalDavBackend $calDavBackend,
	) {
	}

	public function getCalendars(string $principalUri, array $calendarUris = []): array {
		$calendarInfos = $this->calDavBackend->getSubscriptionsForUser($principalUri);

		if (count($calendarUris) > 0) {
			$calendarInfos = array_filter($calendarInfos, fn (array $subscription) => in_array($subscription['uri'], $calendarUris));
		}

		$calendarInfos = array_values(array_filter($calendarInfos));

		$iCalendars = [];
		foreach ($calendarInfos as $calendarInfo) {
			$calendar = new CachedSubscription($this->calDavBackend, $calendarInfo);
			$iCalendars[] = new CachedSubscriptionImpl(
				$calendar,
				$calendarInfo,
				$this->calDavBackend,
			);
		}
		return $iCalendars;
	}
}
