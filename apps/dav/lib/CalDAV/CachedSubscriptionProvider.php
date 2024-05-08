<?php

declare(strict_types=1);

/**
 * @copyright 2024 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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
namespace OCA\DAV\CalDAV;

use OCP\Calendar\ICalendarProvider;

class CachedSubscriptionProvider implements ICalendarProvider {

	public function __construct(
		private CalDavBackend $calDavBackend
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
