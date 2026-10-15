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
use OCA\DAV\Db\PropertyMapper;
use OCA\DAV\ServerFactory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\Exceptions\CalendarException;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarEventBuilder;
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
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;
use Throwable;
use function array_filter;
use function array_map;
use function array_merge;
use function array_values;

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
		private PropertyMapper $propertyMapper,
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
	#[\Override]
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
	#[\Override]
	public function isEnabled(): bool {
		return !empty($this->calendars) || !empty($this->calendarLoaders);
	}

	/**
	 * Registers a calendar
	 *
	 * @since 13.0.0
	 */
	#[\Override]
	public function registerCalendar(ICalendar $calendar): void {
		$this->calendars[$calendar->getKey()] = $calendar;
	}

	/**
	 * Unregisters a calendar
	 *
	 * @since 13.0.0
	 */
	#[\Override]
	public function unregisterCalendar(ICalendar $calendar): void {
		unset($this->calendars[$calendar->getKey()]);
	}

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * calendars are actually requested
	 *
	 * @since 13.0.0
	 */
	#[\Override]
	public function register(\Closure $callable): void {
		$this->calendarLoaders[] = $callable;
	}

	/**
	 * @return ICalendar[]
	 *
	 * @since 13.0.0
	 */
	#[\Override]
	public function getCalendars(): array {
		$this->loadCalendars();

		return array_values($this->calendars);
	}

	/**
	 * removes all registered calendar instances
	 *
	 * @since 13.0.0
	 */
	#[\Override]
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
	#[\Override]
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

	#[\Override]
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

	#[\Override]
	public function newQuery(string $principalUri): ICalendarQuery {
		return new CalendarQuery($principalUri);
	}

	/**
	 * @since 32.0.0
	 *
	 * @throws \OCP\DB\Exception
	 */
	#[\Override]
	public function handleIMip(
		string $userId,
		string $message,
		array $options = [],
	): bool {

		$logContext = [
			'userId' => $userId,
			'options' => $options,
		];

		$userUri = 'principals/users/' . $userId;

		/** @var list<ICalendarIsWritable&IHandleImipMessage> $userCalendars */
		$userCalendars = array_values(array_filter(
			$this->getCalendarsForPrincipal($userUri),
			fn (ICalendar $calendar): bool => $this->canHandleImip($calendar),
		));

		if ($userCalendars === []) {
			$this->logger->warning('iMip message could not be processed because user has no calendar that can process iMip messages', $logContext);
			return false;
		}

		try {
			/** @var VCalendar $vObject|null */
			$vObject = Reader::read($message);
		} catch (ParseException $e) {
			$logContext['exception'] = $e;
			$this->logger->error('iMip message could not be processed because an error occurred while parsing the iMip message', $logContext);
			return false;
		}

		if (!isset($vObject->VEVENT)) {
			$this->logger->warning('iMip message does not contain any event(s)', $logContext);
			return false;
		}
		/** @var VEvent $vEvent */
		$vEvent = $vObject->VEVENT;

		if (!isset($vEvent->UID)) {
			$this->logger->warning('iMip message event does not contains a UID', $logContext);
			return false;
		}

		$logContext['eventUid'] = $vEvent->UID->getValue();

		if (!isset($vEvent->ORGANIZER)) {
			// quirks mode: for Microsoft Exchange Servers use recipient as organizer if no organizer is set
			if (isset($options['recipient']) && $options['recipient'] !== '') {
				$vEvent->add('ORGANIZER', 'mailto:' . $options['recipient']);
			} else {
				$this->logger->warning('iMip message event does not contain an organizer and no recipient was provided', $logContext);
				return false;
			}
		}

		if (!isset($vEvent->ATTENDEE)) {
			$this->logger->warning('iMip message event does not contains any attendees', $logContext);
			return false;
		}

		foreach ($userCalendars as $calendar) {
			if (!empty($calendar->search('', [], ['uid' => $vEvent->UID->getValue()]))) {
				try {
					$calendar->handleIMipMessage($userId, $vObject->serialize());
					return true;
				} catch (CalendarException $e) {
					$logContext['exception'] = $e;
					$this->logger->error('iMip message could not be processed because an error occurred', $logContext);
					return false;
				}
			}
		}

		if (isset($options['absent']) && $options['absent'] === 'create') {
			// use the primary calendar of the user, otherwise the first one that can process iMip messages
			$primaryCalendar = $this->getPrimaryCalendar($userId);
			$calendar = $primaryCalendar !== null && $this->canHandleImip($primaryCalendar)
				? $primaryCalendar
				: $userCalendars[0];

			if (!empty($options['absentCreateStatus'])) {
				$status = strtoupper($options['absentCreateStatus']);

				if (in_array($status, ['TENTATIVE', 'CONFIRMED', 'CANCELLED'], true) === false) {
					$this->logger->warning('iMip message could not be processed because an invalid status was provided for the event', $logContext);
					return false;
				}

				if (isset($vObject->VEVENT->STATUS)) {
					$vObject->VEVENT->STATUS->setValue($status);
				} else {
					$vObject->VEVENT->add('STATUS', $status);
				}
			}

			try {
				$calendar->handleIMipMessage($userId, $vObject->serialize());
			} catch (CalendarException $e) {
				$logContext['exception'] = $e;
				$this->logger->error('iMip message could not be processed because an error occurred', $logContext);
				return false;
			}

			return true;
		}

		$this->logger->warning('iMip message could not be processed because no corresponding event was found in any calendar', $logContext);

		return false;
	}

	/**
	 * Determines if a calendar can be used to process an iMip message
	 *
	 * @psalm-assert-if-true ICalendarIsWritable&IHandleImipMessage $calendar
	 */
	private function canHandleImip(ICalendar $calendar): bool {
		return $calendar instanceof ICalendarIsWritable
			&& $calendar instanceof IHandleImipMessage
			&& $calendar->isWritable()
			&& !$calendar->isDeleted();
	}

	/**
	 * @since 31.0.0
	 *
	 * @throws \OCP\DB\Exception
	 */
	#[\Override]
	public function handleIMipRequest(
		string $principalUri,
		string $sender,
		string $recipient,
		string $calendarData,
	): bool {
		if (empty($principalUri) || !str_starts_with($principalUri, 'principals/users/')) {
			$this->logger->error('Invalid principal URI provided for iMip request');
			return false;
		}
		$userId = substr($principalUri, 17);
		$options = ['recipient' => $recipient];
		return $this->handleIMip($userId, $calendarData, $options);
	}

	/**
	 * @since 25.0.0
	 *
	 * @throws \OCP\DB\Exception
	 */
	#[\Override]
	public function handleIMipReply(
		string $principalUri,
		string $sender,
		string $recipient,
		string $calendarData,
	): bool {
		if (empty($principalUri) || !str_starts_with($principalUri, 'principals/users/')) {
			$this->logger->error('Invalid principal URI provided for iMip reply');
			return false;
		}
		$userId = substr($principalUri, 17);
		$options = ['recipient' => $recipient];
		return $this->handleIMip($userId, $calendarData, $options);
	}

	/**
	 * @since 25.0.0
	 *
	 * @throws \OCP\DB\Exception
	 */
	#[\Override]
	public function handleIMipCancel(
		string $principalUri,
		string $sender,
		?string $replyTo,
		string $recipient,
		string $calendarData,
	): bool {
		if (empty($principalUri) || !str_starts_with($principalUri, 'principals/users/')) {
			$this->logger->error('Invalid principal URI provided for iMip cancel');
			return false;
		}
		$userId = substr($principalUri, 17);
		$options = ['recipient' => $recipient];
		return $this->handleIMip($userId, $calendarData, $options);
	}

	#[\Override]
	public function createEventBuilder(): ICalendarEventBuilder {
		$uid = $this->random->generate(32, ISecureRandom::CHAR_ALPHANUMERIC);
		return new CalendarEventBuilder($uid, $this->timeFactory);
	}

	#[\Override]
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

	public function getPrimaryCalendar(string $userId): ?ICalendar {
		// determine if the principal has a default calendar configured
		$properties = $this->propertyMapper->findPropertyByPathAndName(
			$userId,
			'principals/users/' . $userId,
			'{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL'
		);
		if ($properties === []) {
			return null;
		}
		// extract the calendar URI from the property value
		$propertyValue = $properties[0]->getPropertyvalue() ?? null;
		if (str_starts_with($propertyValue, 'calendars/' . $userId)) {
			$calendarUri = rtrim(str_replace('calendars/' . $userId . '/', '', $propertyValue), '/');
		}
		if (empty($calendarUri)) {
			return null;
		}
		// retrieve the calendar by URI
		$calendars = $this->getCalendarsForPrincipal('principals/users/' . $userId, [$calendarUri]);
		if ($calendars === []) {
			return null;
		}

		return $calendars[0];
	}

}
