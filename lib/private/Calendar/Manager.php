<?php

declare(strict_types=1);

/**
 * @copyright 2017, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Anna Larch <anna.larch@gmx.net>
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
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\Exceptions\CalendarException;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarProvider;
use OCP\Calendar\ICalendarQuery;
use OCP\Calendar\ICreateFromString;
use OCP\Calendar\IHandleImipMessage;
use OCP\Calendar\IManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Property\VCard\DateTime;
use Sabre\VObject\Reader;
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

	private ITimeFactory $timeFactory;


	public function __construct(Coordinator $coordinator,
								ContainerInterface $container,
								LoggerInterface $logger,
								ITimeFactory $timeFactory) {
		$this->coordinator = $coordinator;
		$this->container = $container;
		$this->logger = $logger;
		$this->timeFactory = $timeFactory;
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

	/**
	 * @param string $principalUri
	 * @param array $calendarUris
	 * @return ICreateFromString[]
	 */
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

	/**
	 * @throws \OCP\DB\Exception
	 */
	public function handleIMipReply(string $principalUri, string $sender, string $recipient, string $calendarData): bool {
		/** @var VCalendar $vObject */
		$vObject = Reader::read($calendarData);
		/** @var VEvent $vEvent */
		$vEvent = $vObject->{'VEVENT'};

		// First, we check if the correct method is passed to us
		if (strcasecmp('REPLY', $vObject->{'METHOD'}->getValue()) !== 0) {
			$this->logger->warning('Wrong method provided for processing');
			return false;
		}

		// check if mail recipient and organizer are one and the same
		$organizer = substr($vEvent->{'ORGANIZER'}->getValue(), 7);

		if (strcasecmp($recipient, $organizer) !== 0) {
			$this->logger->warning('Recipient and ORGANIZER must be identical');
			return false;
		}

		//check if the event is in the future
		/** @var DateTime $eventTime */
		$eventTime = $vEvent->{'DTSTART'};
		if ($eventTime->getDateTime()->getTimeStamp() < $this->timeFactory->getTime()) { // this might cause issues with recurrences
			$this->logger->warning('Only events in the future are processed');
			return false;
		}

		$calendars = $this->getCalendarsForPrincipal($principalUri);
		if (empty($calendars)) {
			$this->logger->warning('Could not find any calendars for principal ' . $principalUri);
			return false;
		}

		$found = null;
		// if the attendee has been found in at least one calendar event with the UID of the iMIP event
		// we process it.
		// Benefit: no attendee lost
		// Drawback: attendees that have been deleted will still be able to update their partstat
		foreach ($calendars as $calendar) {
			// We should not search in writable calendars
			if ($calendar instanceof IHandleImipMessage) {
				$o = $calendar->search($sender, ['ATTENDEE'], ['uid' => $vEvent->{'UID'}->getValue()]);
				if (!empty($o)) {
					$found = $calendar;
					$name = $o[0]['uri'];
					break;
				}
			}
		}

		if (empty($found)) {
			$this->logger->info('Event not found in any calendar for principal ' . $principalUri . 'and UID' . $vEvent->{'UID'}->getValue());
			return false;
		}

		try {
			$found->handleIMipMessage($name, $calendarData); // sabre will handle the scheduling behind the scenes
		} catch (CalendarException $e) {
			$this->logger->error('Could not update calendar for iMIP processing', ['exception' => $e]);
			return false;
		}
		return true;
	}

	/**
	 * @since 25.0.0
	 * @throws \OCP\DB\Exception
	 */
	public function handleIMipCancel(string $principalUri, string $sender, ?string $replyTo, string $recipient, string $calendarData): bool {
		$vObject = Reader::read($calendarData);
		/** @var VEvent $vEvent */
		$vEvent = $vObject->{'VEVENT'};

		// First, we check if the correct method is passed to us
		if (strcasecmp('CANCEL', $vObject->{'METHOD'}->getValue()) !== 0) {
			$this->logger->warning('Wrong method provided for processing');
			return false;
		}

		$attendee = substr($vEvent->{'ATTENDEE'}->getValue(), 7);
		if (strcasecmp($recipient, $attendee) !== 0) {
			$this->logger->warning('Recipient must be an ATTENDEE of this event');
			return false;
		}

		// Thirdly, we need to compare the email address the CANCEL is coming from (in Mail)
		// or the Reply- To Address submitted with the CANCEL email
		// to the email address in the ORGANIZER.
		// We don't want to accept a CANCEL request from just anyone
		$organizer = substr($vEvent->{'ORGANIZER'}->getValue(), 7);
		$isNotOrganizer = ($replyTo !== null) ? (strcasecmp($sender, $organizer) !== 0 && strcasecmp($replyTo, $organizer) !== 0) : (strcasecmp($sender, $organizer) !== 0);
		if ($isNotOrganizer) {
			$this->logger->warning('Sender must be the ORGANIZER of this event');
			return false;
		}

		//check if the event is in the future
		/** @var DateTime $eventTime */
		$eventTime = $vEvent->{'DTSTART'};
		if ($eventTime->getDateTime()->getTimeStamp() < $this->timeFactory->getTime()) { // this might cause issues with recurrences
			$this->logger->warning('Only events in the future are processed');
			return false;
		}

		// Check if we have a calendar to work with
		$calendars = $this->getCalendarsForPrincipal($principalUri);
		if (empty($calendars)) {
			$this->logger->warning('Could not find any calendars for principal ' . $principalUri);
			return false;
		}

		$found = null;
		// if the attendee has been found in at least one calendar event with the UID of the iMIP event
		// we process it.
		// Benefit: no attendee lost
		// Drawback: attendees that have been deleted will still be able to update their partstat
		foreach ($calendars as $calendar) {
			// We should not search in writable calendars
			if ($calendar instanceof IHandleImipMessage) {
				$o = $calendar->search($recipient, ['ATTENDEE'], ['uid' => $vEvent->{'UID'}->getValue()]);
				if (!empty($o)) {
					$found = $calendar;
					$name = $o[0]['uri'];
					break;
				}
			}
		}

		if (empty($found)) {
			$this->logger->info('Event not found in any calendar for principal ' . $principalUri . 'and UID' . $vEvent->{'UID'}->getValue());
			// this is a safe operation
			// we can ignore events that have been cancelled but were not in the calendar anyway
			return true;
		}

		try {
			$found->handleIMipMessage($name, $calendarData); // sabre will handle the scheduling behind the scenes
			return true;
		} catch (CalendarException $e) {
			$this->logger->error('Could not update calendar for iMIP processing', ['exception' => $e]);
			return false;
		}
	}
}
