<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Calendar;

use DateTimeInterface;
use OC\AppFramework\Bootstrap\Coordinator;
use OCA\DAV\CalDAV\Auth\CustomPrincipalPlugin;
use OCA\DAV\ServerFactory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\Exceptions\CalendarException;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarEventBuilder;
use OCP\Calendar\ICalendarIsShared;
use OCP\Calendar\ICalendarIsWritable;
use OCP\Calendar\ICalendarProvider;
use OCP\Calendar\ICalendarQuery;
use OCP\Calendar\ICreateFromString;
use OCP\Calendar\IHandleImipMessage;
use OCP\Calendar\IManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Component\VFreeBusy;
use Sabre\VObject\Property\VCard\DateTime;
use Sabre\VObject\Reader;
use Throwable;
use function array_map;
use function array_merge;

class Manager implements IManager {
	/**
	 * @var ICalendar[] holds all registered calendars
	 */
	private array $calendars = [];

	/**
	 * @var \Closure[] to call to load/register calendar providers
	 */
	private array $calendarLoaders = [];

	public function __construct(
		private Coordinator $coordinator,
		private ContainerInterface $container,
		private LoggerInterface $logger,
		private ITimeFactory $timeFactory,
		private ISecureRandom $random,
		private IUserManager $userManager,
		private ServerFactory $serverFactory,
	) {
	}

	/**
	 * This function is used to search and find objects within the user's calendars.
	 * In case $pattern is empty all events/journals/todos will be returned.
	 *
	 * @param string $pattern which should match within the $searchProperties
	 * @param array $searchProperties defines the properties within the query pattern should match
	 * @param array $options - optional parameters:
	 *                       ['timerange' => ['start' => new DateTime(...), 'end' => new DateTime(...)]]
	 * @param integer|null $limit - limit number of search results
	 * @param integer|null $offset - offset for paging of search results
	 * @return array an array of events/journals/todos which are arrays of arrays of key-value-pairs
	 * @since 13.0.0
	 */
	public function search(
		$pattern,
		array $searchProperties = [],
		array $options = [],
		$limit = null,
		$offset = null,
	): array {
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
	public function isEnabled(): bool {
		return !empty($this->calendars) || !empty($this->calendarLoaders);
	}

	/**
	 * Registers a calendar
	 *
	 * @since 13.0.0
	 */
	public function registerCalendar(ICalendar $calendar): void {
		$this->calendars[$calendar->getKey()] = $calendar;
	}

	/**
	 * Unregisters a calendar
	 *
	 * @since 13.0.0
	 */
	public function unregisterCalendar(ICalendar $calendar): void {
		unset($this->calendars[$calendar->getKey()]);
	}

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * calendars are actually requested
	 *
	 * @since 13.0.0
	 */
	public function register(\Closure $callable): void {
		$this->calendarLoaders[] = $callable;
	}

	/**
	 * @return ICalendar[]
	 *
	 * @since 13.0.0
	 */
	public function getCalendars(): array {
		$this->loadCalendars();

		return array_values($this->calendars);
	}

	/**
	 * removes all registered calendar instances
	 *
	 * @since 13.0.0
	 */
	public function clear(): void {
		$this->calendars = [];
		$this->calendarLoaders = [];
	}

	/**
	 * loads all calendars
	 */
	private function loadCalendars(): void {
		foreach ($this->calendarLoaders as $callable) {
			$callable($this);
		}
		$this->calendarLoaders = [];
	}

	/**
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
				$o['calendar-uri'] = $calendar->getUri();
				$results[] = $o;
			}
		}
		return $results;
	}

	public function newQuery(string $principalUri): ICalendarQuery {
		return new CalendarQuery($principalUri);
	}

	/**
	 * @since 31.0.0
	 * @throws \OCP\DB\Exception
	 */
	public function handleIMipRequest(
		string $principalUri,
		string $sender,
		string $recipient,
		string $calendarData,
	): bool {

		$userCalendars = $this->getCalendarsForPrincipal($principalUri);
		if (empty($userCalendars)) {
			$this->logger->warning('iMip message could not be processed because user has no calendars');
			return false;
		}

		/** @var VCalendar $vObject|null */
		$calendarObject = Reader::read($calendarData);

		if (!isset($calendarObject->METHOD) || $calendarObject->METHOD->getValue() !== 'REQUEST') {
			$this->logger->warning('iMip message contains an incorrect or invalid method');
			return false;
		}

		if (!isset($calendarObject->VEVENT)) {
			$this->logger->warning('iMip message contains no event');
			return false;
		}

		$eventObject = $calendarObject->VEVENT;

		if (!isset($eventObject->UID)) {
			$this->logger->warning('iMip message event dose not contains a UID');
			return false;
		}

		if (!isset($eventObject->ATTENDEE)) {
			$this->logger->warning('iMip message event dose not contains any attendees');
			return false;
		}

		foreach ($eventObject->ATTENDEE as $entry) {
			$address = trim(str_replace('mailto:', '', $entry->getValue()));
			if ($address === $recipient) {
				$attendee = $address;
				break;
			}
		}
		if (!isset($attendee)) {
			$this->logger->warning('iMip message event does not contain a attendee that matches the recipient');
			return false;
		}

		foreach ($userCalendars as $calendar) {

			if (!$calendar instanceof ICalendarIsWritable && !$calendar instanceof ICalendarIsShared) {
				continue;
			}

			if ($calendar->isDeleted() || !$calendar->isWritable() || $calendar->isShared()) {
				continue;
			}

			if (!empty($calendar->search($recipient, ['ATTENDEE'], ['uid' => $eventObject->UID->getValue()]))) {
				try {
					if ($calendar instanceof IHandleImipMessage) {
						$calendar->handleIMipMessage('', $calendarData);
					}
					return true;
				} catch (CalendarException $e) {
					$this->logger->error('An error occurred while processing the iMip message event', ['exception' => $e]);
					return false;
				}
			}
		}

		$this->logger->warning('iMip message event could not be processed because the no corresponding event was found in any calendar');
		return false;
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	public function handleIMipReply(
		string $principalUri,
		string $sender,
		string $recipient,
		string $calendarData,
	): bool {
		/** @var VCalendar $vObject|null */
		$vObject = Reader::read($calendarData);

		if ($vObject === null) {
			return false;
		}

		/** @var VEvent|null $vEvent */
		$vEvent = $vObject->{'VEVENT'};

		if ($vEvent === null) {
			return false;
		}

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
	public function handleIMipCancel(
		string $principalUri,
		string $sender,
		?string $replyTo,
		string $recipient,
		string $calendarData,
	): bool {
		/** @var VCalendar $vObject|null */
		$vObject = Reader::read($calendarData);

		if ($vObject === null) {
			return false;
		}

		/** @var VEvent|null $vEvent */
		$vEvent = $vObject->{'VEVENT'};

		if ($vEvent === null) {
			return false;
		}

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

	public function createEventBuilder(): ICalendarEventBuilder {
		$uid = $this->random->generate(32, ISecureRandom::CHAR_ALPHANUMERIC);
		return new CalendarEventBuilder($uid, $this->timeFactory);
	}

	public function checkAvailability(
		DateTimeInterface $start,
		DateTimeInterface $end,
		IUser $organizer,
		array $attendees,
	): array {
		$organizerMailto = 'mailto:' . $organizer->getEMailAddress();
		$request = new VCalendar();
		$request->METHOD = 'REQUEST';
		$request->add('VFREEBUSY', [
			'DTSTART' => $start,
			'DTEND' => $end,
			'ORGANIZER' => $organizerMailto,
			'ATTENDEE' => $organizerMailto,
		]);

		$mailtoLen = strlen('mailto:');
		foreach ($attendees as $attendee) {
			if (str_starts_with($attendee, 'mailto:')) {
				$attendee = substr($attendee, $mailtoLen);
			}

			$attendeeUsers = $this->userManager->getByEmail($attendee);
			if ($attendeeUsers === []) {
				continue;
			}

			$request->VFREEBUSY->add('ATTENDEE', "mailto:$attendee");
		}

		$organizerUid = $organizer->getUID();
		$server = $this->serverFactory->createAttendeeAvailabilityServer();
		/** @var CustomPrincipalPlugin $plugin */
		$plugin = $server->getPlugin('auth');
		$plugin->setCurrentPrincipal("principals/users/$organizerUid");

		$request = new Request(
			'POST',
			"/calendars/$organizerUid/outbox/",
			[
				'Content-Type' => 'text/calendar',
				'Depth' => 0,
			],
			$request->serialize(),
		);
		$response = new Response();
		$server->invokeMethod($request, $response, false);

		$xmlService = new \Sabre\Xml\Service();
		$xmlService->elementMap = [
			'{urn:ietf:params:xml:ns:caldav}response' => 'Sabre\Xml\Deserializer\keyValue',
			'{urn:ietf:params:xml:ns:caldav}recipient' => 'Sabre\Xml\Deserializer\keyValue',
		];
		$parsedResponse = $xmlService->parse($response->getBodyAsString());

		$result = [];
		foreach ($parsedResponse as $freeBusyResponse) {
			$freeBusyResponse = $freeBusyResponse['value'];
			if ($freeBusyResponse['{urn:ietf:params:xml:ns:caldav}request-status'] !== '2.0;Success') {
				continue;
			}

			$freeBusyResponseData = \Sabre\VObject\Reader::read(
				$freeBusyResponse['{urn:ietf:params:xml:ns:caldav}calendar-data']
			);

			$attendee = substr(
				$freeBusyResponse['{urn:ietf:params:xml:ns:caldav}recipient']['{DAV:}href'],
				$mailtoLen,
			);

			$vFreeBusy = $freeBusyResponseData->VFREEBUSY;
			if (!($vFreeBusy instanceof VFreeBusy)) {
				continue;
			}

			// TODO: actually check values of FREEBUSY properties to find a free slot
			$result[] = new AvailabilityResult($attendee, $vFreeBusy->isFree($start, $end));
		}

		return $result;
	}
}
