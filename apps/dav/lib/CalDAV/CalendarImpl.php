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
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Component\VTimeZone;
use Sabre\VObject\ITip\Message;
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
	 * Create a new calendar event for this calendar
	 * by way of an ICS string
	 *
	 * @param string $name the file name - needs to contain the .ics ending
	 * @param string $calendarData a string containing a valid VEVENT ics
	 *
	 * @throws CalendarException
	 */
	public function createFromString(string $name, string $calendarData): void {
		$server = new InvitationResponseServer(false);

		/** @var CustomPrincipalPlugin $plugin */
		$plugin = $server->getServer()->getPlugin('auth');
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
		$schedulingPlugin = $server->getServer()->getPlugin('caldav-schedule');
		$schedulingPlugin->setPathOfCalendarObjectChange($fullCalendarFilename);

		$stream = fopen('php://memory', 'rb+');
		fwrite($stream, $calendarData);
		rewind($stream);
		try {
			$server->getServer()->createFile($fullCalendarFilename, $stream);
		} catch (Conflict $e) {
			throw new CalendarException('Could not create new calendar event: ' . $e->getMessage(), 0, $e);
		} finally {
			fclose($stream);
		}
	}

	/**
	 * @throws CalendarException
	 */
	public function handleIMipMessage(string $name, string $calendarData): void {
		$server = $this->getInvitationResponseServer();

		/** @var CustomPrincipalPlugin $plugin */
		$plugin = $server->getServer()->getPlugin('auth');
		// we're working around the previous implementation
		// that only allowed the public system principal to be used
		// so set the custom principal here
		$plugin->setCurrentPrincipal($this->calendar->getPrincipalURI());

		if (empty($this->calendarInfo['uri'])) {
			throw new CalendarException('Could not write to calendar as URI parameter is missing');
		}
		// Force calendar change URI
		/** @var Schedule\Plugin $schedulingPlugin */
		$schedulingPlugin = $server->getServer()->getPlugin('caldav-schedule');
		// Let sabre handle the rest
		$iTipMessage = new Message();
		/** @var VCalendar $vObject */
		$vObject = Reader::read($calendarData);
		/** @var VEvent $vEvent */
		$vEvent = $vObject->{'VEVENT'};

		if ($vObject->{'METHOD'} === null) {
			throw new CalendarException('No Method provided for scheduling data. Could not process message');
		}

		if (!isset($vEvent->{'ORGANIZER'}) || !isset($vEvent->{'ATTENDEE'})) {
			throw new CalendarException('Could not process scheduling data, neccessary data missing from ICAL');
		}
		$organizer = $vEvent->{'ORGANIZER'}->getValue();
		$attendee = $vEvent->{'ATTENDEE'}->getValue();

		$iTipMessage->method = $vObject->{'METHOD'}->getValue();
		if ($iTipMessage->method === 'REQUEST') {
			$iTipMessage->sender = $organizer;
			$iTipMessage->recipient = $attendee;
		} elseif ($iTipMessage->method === 'REPLY') {
			if ($server->isExternalAttendee($vEvent->{'ATTENDEE'}->getValue())) {
				$iTipMessage->recipient = $organizer;
			} else {
				$iTipMessage->recipient = $attendee;
			}
			$iTipMessage->sender = $attendee;
		} elseif ($iTipMessage->method === 'CANCEL') {
			$iTipMessage->recipient = $attendee;
			$iTipMessage->sender = $organizer;
		}
		$iTipMessage->uid = isset($vEvent->{'UID'}) ? $vEvent->{'UID'}->getValue() : '';
		$iTipMessage->component = 'VEVENT';
		$iTipMessage->sequence = isset($vEvent->{'SEQUENCE'}) ? (int)$vEvent->{'SEQUENCE'}->getValue() : 0;
		$iTipMessage->message = $vObject;
		$server->server->emit('schedule', [$iTipMessage]);
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
