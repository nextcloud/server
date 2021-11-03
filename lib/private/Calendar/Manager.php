<?php

declare(strict_types=1);

/**
 * @copyright 2017, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
namespace OC\Calendar;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarProvider;
use OCP\Calendar\ICalendarQuery;
use OCP\Calendar\IManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use function array_map;
use function array_merge;

class Manager implements IManager {

	/**
	 * @var ICalendar[] holds all registered calendars
	 */
	private $calendars = [];

	/**
	 * @var \Closure[] to call to load/register calendar providers
	 */
	private $calendarLoaders = [];

	/** @var Coordinator */
	private $coordinator;

	/** @var ContainerInterface */
	private $container;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(Coordinator $coordinator,
								ContainerInterface $container,
								LoggerInterface $logger) {
		$this->coordinator = $coordinator;
		$this->container = $container;
		$this->logger = $logger;
	}

	/**
	 * This function is used to search and find objects within the user's calendars.
	 * In case $pattern is empty all events/journals/todos will be returned.
	 *
	 * @param string $pattern which should match within the $searchProperties
	 * @param array $searchProperties defines the properties within the query pattern should match
	 * @param array $options - optional parameters:
	 * 	['timerange' => ['start' => new DateTime(...), 'end' => new DateTime(...)]]
	 * @param integer|null $limit - limit number of search results
	 * @param integer|null $offset - offset for paging of search results
	 * @return array an array of events/journals/todos which are arrays of arrays of key-value-pairs
	 * @since 13.0.0
	 */
	public function search($pattern, array $searchProperties = [], array $options = [], $limit = null, $offset = null) {
		$this->loadCalendars();
		$result = [];
		foreach ($this->calendars as $calendar) {
			$r = $calendar->search($pattern, $searchProperties, $options, $limit, $offset);
			foreach ($r as $o) {
				$o['calendar-key'] = $calendar->getKey();
				$result[] = $o;
			}
		}

		return $result;
	}

	/**
	 * Check if calendars are available
	 *
	 * @return bool true if enabled, false if not
	 * @since 13.0.0
	 */
	public function isEnabled() {
		return !empty($this->calendars) || !empty($this->calendarLoaders);
	}

	/**
	 * Registers a calendar
	 *
	 * @param ICalendar $calendar
	 * @return void
	 * @since 13.0.0
	 */
	public function registerCalendar(ICalendar $calendar) {
		$this->calendars[$calendar->getKey()] = $calendar;
	}

	/**
	 * Unregisters a calendar
	 *
	 * @param ICalendar $calendar
	 * @return void
	 * @since 13.0.0
	 */
	public function unregisterCalendar(ICalendar $calendar) {
		unset($this->calendars[$calendar->getKey()]);
	}

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * calendars are actually requested
	 *
	 * @param \Closure $callable
	 * @return void
	 * @since 13.0.0
	 */
	public function register(\Closure $callable) {
		$this->calendarLoaders[] = $callable;
	}

	/**
	 * @return ICalendar[]
	 * @since 13.0.0
	 */
	public function getCalendars() {
		$this->loadCalendars();

		return array_values($this->calendars);
	}

	/**
	 * removes all registered calendar instances
	 * @return void
	 * @since 13.0.0
	 */
	public function clear() {
		$this->calendars = [];
		$this->calendarLoaders = [];
	}

	/**
	 * loads all calendars
	 */
	private function loadCalendars() {
		foreach ($this->calendarLoaders as $callable) {
			$callable($this);
		}
		$this->calendarLoaders = [];
	}

	public function getCalendarsForPrincipal(string $principalUri, array $calendarUris = []): array {
		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			return [];
		}

		return array_merge(
			...array_map(function ($registration) use ($principalUri, $calendarUris) {
				try {
					/** @var ICalendarProvider $provider */
					$provider = $this->container->get($registration->getService());
				} catch (Throwable $e) {
					$this->logger->error('Could not load calendar provider ' . $registration->getService() . ': ' . $e->getMessage(), [
						'exception' => $e,
					]);
					return [];
				}

				return $provider->getCalendars($principalUri, $calendarUris);
			}, $context->getCalendarProviders())
		);
	}

	public function searchForPrincipal(ICalendarQuery $query): array {
		/** @var CalendarQuery $query */
		$calendars = $this->getCalendarsForPrincipal(
			$query->getPrincipalUri(),
			$query->getCalendarUris(),
		);

		$results = [];
		/** @var ICalendar $calendar */
		foreach ($calendars as $calendar) {
			$r = $calendar->search(
				$query->getSearchPattern() ?? '',
				$query->getSearchProperties(),
				$query->getOptions(),
				$query->getLimit(),
				$query->getOffset()
			);

			foreach ($r as $o) {
				$o['calendar-key'] = $calendar->getKey();
				$results[] = $o;
			}
		}
		return $results;
	}

	public function newQuery(string $principalUri): ICalendarQuery {
		return new CalendarQuery($principalUri);
	}
}
