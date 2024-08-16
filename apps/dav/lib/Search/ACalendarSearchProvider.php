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
use Sabre\VObject\Reader;

/**
 * Class ACalendarSearchProvider
 *
 * @package OCA\DAV\Search
 */
abstract class ACalendarSearchProvider implements IProvider {

	/** @var IAppManager */
	protected $appManager;

	/** @var IL10N */
	protected $l10n;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var CalDavBackend */
	protected $backend;

	/**
	 * ACalendarSearchProvider constructor.
	 *
	 * @param IAppManager $appManager
	 * @param IL10N $l10n
	 * @param IURLGenerator $urlGenerator
	 * @param CalDavBackend $backend
	 */
	public function __construct(IAppManager $appManager,
		IL10N $l10n,
		IURLGenerator $urlGenerator,
		CalDavBackend $backend) {
		$this->appManager = $appManager;
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->backend = $backend;
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
			$calendarsById[(int) $calendar['id']] = $calendar;
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
			$subscriptionsById[(int) $subscription['id']] = $subscription;
		}

		return $subscriptionsById;
	}

	/**
	 * Returns the primary VEvent / VJournal / VTodo component
	 * If it's a component with recurrence-ids, it will return
	 * the primary component
	 *
	 * TODO: It would be a nice enhancement to show recurrence-exceptions
	 * as individual search-results.
	 * For now we will just display the primary element of a recurrence-set.
	 *
	 * @param string $calendarData
	 * @param string $componentName
	 * @return Component
	 */
	protected function getPrimaryComponent(string $calendarData, string $componentName): Component {
		$vCalendar = Reader::read($calendarData, Reader::OPTION_FORGIVING);

		$components = $vCalendar->select($componentName);
		if (count($components) === 1) {
			return $components[0];
		}

		// If it's a recurrence-set, take the primary element
		foreach ($components as $component) {
			/** @var Component $component */
			if (!$component->{'RECURRENCE-ID'}) {
				return $component;
			}
		}

		// In case of error, just fallback to the first element in the set
		return $components[0];
	}
}
