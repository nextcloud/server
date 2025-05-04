<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use Generator;
use OCA\DAV\CalDAV\Auth\CustomPrincipalPlugin;
use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;
use OCA\DAV\Connector\Sabre\Server;
use OCP\Calendar\CalendarExportOptions;
use OCP\Calendar\Exceptions\CalendarException;
use OCP\Calendar\ICalendarExport;
use OCP\Calendar\ICalendarIsEnabled;
use OCP\Calendar\ICalendarIsShared;
use OCP\Calendar\ICalendarIsWritable;
use OCP\Calendar\ICreateFromString;
use OCP\Calendar\IHandleImipMessage;
use OCP\Constants;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\DAV\Exception\Conflict;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VTimeZone;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\ParseException;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;

use function Sabre\Uri\split as uriSplit;

class CalendarImpl implements ICreateFromString, IHandleImipMessage, ICalendarIsWritable, ICalendarIsShared, ICalendarExport, ICalendarIsEnabled {
	public function __construct(
		private Calendar $calendar,
		/** @var array<string, mixed> */
		private array $calendarInfo,
		private CalDavBackend $backend,
	) {
	}

	private const DAV_PROPERTY_USER_ADDRESS = '{http://sabredav.org/ns}email-address';
	private const DAV_PROPERTY_USER_ADDRESSES = '{urn:ietf:params:xml:ns:caldav}calendar-user-address-set';

	/**
	 * @return string defining the technical unique key
	 * @since 13.0.0
	 */
	public function getKey(): string {
		return (string)$this->calendarInfo['id'];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getUri(): string {
		return $this->calendarInfo['uri'];
	}

	/**
	 * @return string the principal URI of the calendar owner
	 * @since 32.0.0
	 */
	public function getPrincipalUri(): string {
		return $this->calendarInfo['principaluri'];
	}

	/**
	 * In comparison to getKey() this function returns a human readable (maybe translated) name
	 * @since 13.0.0
	 */
	public function getDisplayName(): ?string {
		return $this->calendarInfo['{DAV:}displayname'];
	}

	/**
	 * Calendar color
	 * @since 13.0.0
	 */
	public function getDisplayColor(): ?string {
		return $this->calendarInfo['{http://apple.com/ns/ical/}calendar-color'];
	}

	public function getSchedulingTransparency(): ?ScheduleCalendarTransp {
		return $this->calendarInfo['{' . \OCA\DAV\CalDAV\Schedule\Plugin::NS_CALDAV . '}schedule-calendar-transp'];
	}

	public function getSchedulingTimezone(): ?VTimeZone {
		$tzProp = '{' . \OCA\DAV\CalDAV\Schedule\Plugin::NS_CALDAV . '}calendar-timezone';
		if (!isset($this->calendarInfo[$tzProp])) {
			return null;
		}
		// This property contains a VCALENDAR with a single VTIMEZONE
		/** @var string $timezoneProp */
		$timezoneProp = $this->calendarInfo[$tzProp];
		/** @var VCalendar $vobj */
		$vobj = Reader::read($timezoneProp);
		$components = $vobj->getComponents();
		if (empty($components)) {
			return null;
		}
		/** @var VTimeZone $vtimezone */
		$vtimezone = $components[0];
		return $vtimezone;
	}

	public function search(string $pattern, array $searchProperties = [], array $options = [], $limit = null, $offset = null): array {
		return $this->backend->search($this->calendarInfo, $pattern,
			$searchProperties, $options, $limit, $offset);
	}

	/**
	 * @return int build up using \OCP\Constants
	 * @since 13.0.0
	 */
	public function getPermissions(): int {
		$permissions = $this->calendar->getACL();
		$result = 0;
		foreach ($permissions as $permission) {
			if ($this->calendarInfo['principaluri'] !== $permission['principal']) {
				continue;
			}

			switch ($permission['privilege']) {
				case '{DAV:}read':
					$result |= Constants::PERMISSION_READ;
					break;
				case '{DAV:}write':
					$result |= Constants::PERMISSION_CREATE;
					$result |= Constants::PERMISSION_UPDATE;
					break;
				case '{DAV:}all':
					$result |= Constants::PERMISSION_ALL;
					break;
			}
		}

		return $result;
	}

	/**
	 * @since 32.0.0
	 */
	public function isEnabled(): bool {
		return $this->calendarInfo['{http://owncloud.org/ns}calendar-enabled'] ?? true;
	}

	/**
	 * @since 31.0.0
	 */
	public function isWritable(): bool {
		return $this->calendar->canWrite();
	}

	/**
	 * @since 26.0.0
	 */
	public function isDeleted(): bool {
		return $this->calendar->isDeleted();
	}

	/**
	 * @since 31.0.0
	 */
	public function isShared(): bool {
		return $this->calendar->isShared();
	}

	/**
	 * @throws CalendarException
	 */
	private function createFromStringInServer(
		string $name,
		string $calendarData,
		Server $server,
	): void {
		/** @var CustomPrincipalPlugin $plugin */
		$plugin = $server->getPlugin('auth');
		// we're working around the previous implementation
		// that only allowed the public system principal to be used
		// so set the custom principal here
		$plugin->setCurrentPrincipal($this->calendar->getPrincipalURI());

		if (empty($this->calendarInfo['uri'])) {
			throw new CalendarException('Could not write to calendar as URI parameter is missing');
		}

		// Build full calendar path
		[, $user] = uriSplit($this->calendar->getPrincipalURI());
		$fullCalendarFilename = sprintf('calendars/%s/%s/%s', $user, $this->calendarInfo['uri'], $name);

		// Force calendar change URI
		/** @var Schedule\Plugin $schedulingPlugin */
		$schedulingPlugin = $server->getPlugin('caldav-schedule');
		$schedulingPlugin->setPathOfCalendarObjectChange($fullCalendarFilename);

		$stream = fopen('php://memory', 'rb+');
		fwrite($stream, $calendarData);
		rewind($stream);
		try {
			$server->createFile($fullCalendarFilename, $stream);
		} catch (Conflict $e) {
			throw new CalendarException('Could not create new calendar event: ' . $e->getMessage(), 0, $e);
		} finally {
			fclose($stream);
		}
	}

	public function createFromString(string $name, string $calendarData): void {
		$server = new EmbeddedCalDavServer(false);
		$this->createFromStringInServer($name, $calendarData, $server->getServer());
	}

	public function createFromStringMinimal(string $name, string $calendarData): void {
		$server = new InvitationResponseServer(false);
		$this->createFromStringInServer($name, $calendarData, $server->getServer());
	}

	/**
	 * @throws CalendarException
	 */
	public function handleIMipMessage(string $name, string $calendarData): void {

		try {
			/** @var VCalendar $vObject|null */
			$vObject = Reader::read($calendarData);
		} catch (ParseException $e) {
			throw new CalendarException('iMip message could not be processed because an error occurred while parsing the iMip message', 0, $e);
		}
		// validate the iMip message
		if (!isset($vObject->METHOD)) {
			throw new CalendarException('iMip message contains no valid method');
		}
		if (!isset($vObject->VEVENT)) {
			throw new CalendarException('iMip message contains no event');
		}
		if (!isset($vObject->VEVENT->UID)) {
			throw new CalendarException('iMip message event dose not contain a UID');
		}
		if (!isset($vObject->VEVENT->ORGANIZER)) {
			throw new CalendarException('iMip message event dose not contain an organizer');
		}
		if (!isset($vObject->VEVENT->ATTENDEE)) {
			throw new CalendarException('iMip message event dose not contain an attendee');
		}
		if (empty($this->calendarInfo['uri'])) {
			throw new CalendarException('Could not write to calendar as URI parameter is missing');
		}
		// construct dav server
		$server = $this->getInvitationResponseServer();
		/** @var CustomPrincipalPlugin $authPlugin */
		$authPlugin = $server->getServer()->getPlugin('auth');
		// we're working around the previous implementation
		// that only allowed the public system principal to be used
		// so set the custom principal here
		$authPlugin->setCurrentPrincipal($this->calendar->getPrincipalURI());
		// retrieve all users addresses
		$userProperties = $server->getServer()->getProperties($this->calendar->getPrincipalURI(), [ self::DAV_PROPERTY_USER_ADDRESS, self::DAV_PROPERTY_USER_ADDRESSES ]);
		$userAddress = 'mailto:' . ($userProperties[self::DAV_PROPERTY_USER_ADDRESS] ?? null);
		$userAddresses = $userProperties[self::DAV_PROPERTY_USER_ADDRESSES]->getHrefs() ?? [];
		$userAddresses = array_map('strtolower', array_map('urldecode', $userAddresses));
		// validate the method, recipient and sender
		$imipMethod = strtoupper($vObject->METHOD->getValue());
		if (in_array($imipMethod, ['REPLY', 'REFRESH'], true)) {
			// extract sender (REPLY and REFRESH method should only have one attendee)
			$sender = strtolower($vObject->VEVENT->ATTENDEE->getValue());
			// extract and verify the recipient
			$recipient = strtolower($vObject->VEVENT->ORGANIZER->getValue());
			if (!in_array($recipient, $userAddresses, true)) {
				throw new CalendarException('iMip message dose not contain an organizer that matches the user');
			}
			// if the recipient address is not the same as the user address this means an alias was used
			// the iTip broker uses the users primary email address during processing
			if ($userAddress !== $recipient) {
				$recipient = $userAddress;
			}
		} elseif (in_array($imipMethod, ['PUBLISH', 'REQUEST', 'ADD', 'CANCEL'], true)) {
			// extract sender
			$sender = strtolower($vObject->VEVENT->ORGANIZER->getValue());
			// extract and verify the recipient
			foreach ($vObject->VEVENT->ATTENDEE as $attendee) {
				$recipient = strtolower($attendee->getValue());
				if (in_array($recipient, $userAddresses, true)) {
					break;
				}
				$recipient = null;
			}
			if ($recipient === null) {
				throw new CalendarException('iMip message dose not contain an attendee that matches the user');
			}
			// if the recipient address is not the same as the user address this means an alias was used
			// the iTip broker uses the users primary email address during processing
			if ($userAddress !== $recipient) {
				$recipient = $userAddress;
			}
		} else {
			throw new CalendarException('iMip message contains a method that is not supported: ' . $imipMethod);
		}
		// generate the iTip message
		$iTip = new Message();
		$iTip->method = $imipMethod;
		$iTip->sender = $sender;
		$iTip->recipient = $recipient;
		$iTip->component = 'VEVENT';
		$iTip->uid = $vObject->VEVENT->UID->getValue();
		$iTip->sequence = isset($vObject->VEVENT->SEQUENCE) ? (int)$vObject->VEVENT->SEQUENCE->getValue() : 1;
		$iTip->message = $vObject;

		$server->server->emit('schedule', [$iTip]);
	}

	public function getInvitationResponseServer(): InvitationResponseServer {
		return new InvitationResponseServer(false);
	}

	/**
	 * Export objects
	 *
	 * @since 32.0.0
	 *
	 * @return Generator<mixed, \Sabre\VObject\Component\VCalendar, mixed, mixed>
	 */
	public function export(?CalendarExportOptions $options = null): Generator {
		foreach (
			$this->backend->exportCalendar(
				$this->calendarInfo['id'],
				$this->backend::CALENDAR_TYPE_CALENDAR,
				$options
			) as $event
		) {
			$vObject = Reader::read($event['calendardata']);
			if ($vObject instanceof VCalendar) {
				yield $vObject;
			}
		}
	}

}
