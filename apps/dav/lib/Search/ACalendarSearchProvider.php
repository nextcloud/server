<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Search;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Search\IProvider;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;

/**
 * Class ACalendarSearchProvider
 *
 * @package OCA\DAV\Search
 */
abstract class ACalendarSearchProvider implements IProvider {

	/**
	 * ACalendarSearchProvider constructor.
	 *
	 * @param IAppManager $appManager
	 * @param IL10N $l10n
	 * @param IURLGenerator $urlGenerator
	 * @param CalDavBackend $backend
	 */
	public function __construct(
		protected IAppManager $appManager,
		protected IL10N $l10n,
		protected IURLGenerator $urlGenerator,
		protected CalDavBackend $backend,
	) {
	}

	/**
	 * Get an associative array of calendars
	 * calendarId => calendar
	 *
	 * @param string $principalUri
	 * @return array
	 */
	protected function getSortedCalendars(string $principalUri): array {
		$calendars = $this->backend->getCalendarsForUser($principalUri);
		$calendarsById = [];
		foreach ($calendars as $calendar) {
			$calendarsById[(int)$calendar['id']] = $calendar;
		}

		return $calendarsById;
	}

	/**
	 * Get an associative array of subscriptions
	 * subscriptionId => subscription
	 *
	 * @param string $principalUri
	 * @return array
	 */
	protected function getSortedSubscriptions(string $principalUri): array {
		$subscriptions = $this->backend->getSubscriptionsForUser($principalUri);
		$subscriptionsById = [];
		foreach ($subscriptions as $subscription) {
			$subscriptionsById[(int)$subscription['id']] = $subscription;
		}

		return $subscriptionsById;
	}

	/**
	 * Returns the primary VEvent / VJournal / VTodo component
	 *
	 * If it's a component with recurrence-ids, it will return
	 * the primary component
	 *
	 * TODO: It would be a nice enhancement to show recurrence-exceptions
	 * as individual search-results.
	 *
	 * For now we will just display the primary element of a recurrence-set.
	 *
	 * Returns null when the calendar has no component of the requested type.
	 *
	 * @param VCalendar $vCalendar
	 * @param string $componentName
	 * @return Component|null
	 */
	protected function getPrimaryComponent(VCalendar $vCalendar, string $componentName): ?Component {
		$first = null;
		foreach ($vCalendar->select($componentName) as $component) {
			/** @var Component $component */
			// Prefer the recurrence-set master (no RECURRENCE-ID); otherwise the first element.
			$first ??= $component;
			if (!$component->{'RECURRENCE-ID'}) {
				return $component;
			}
		}

		return $first;
	}
}
