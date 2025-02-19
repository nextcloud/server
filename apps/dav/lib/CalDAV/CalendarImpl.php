<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use Generator;
use InvalidArgumentException;
use OCA\DAV\CalDAV\Auth\CustomPrincipalPlugin;
use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;
use OCP\Calendar\CalendarExportOptions;
use OCP\Calendar\CalendarImportOptions;
use OCP\Calendar\Exceptions\CalendarException;
use OCP\Calendar\ICalendarExport;
use OCP\Calendar\ICalendarImport;
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
use Sabre\VObject\Node;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;
use Sabre\VObject\UUIDUtil;

use function Sabre\Uri\split as uriSplit;

class CalendarImpl implements ICreateFromString, IHandleImipMessage, ICalendarIsWritable, ICalendarIsShared, ICalendarImport, ICalendarExport {
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

	/**
	 * @param string $pattern which should match within the $searchProperties
	 * @param array $searchProperties defines the properties within the query pattern should match
	 * @param array $options - optional parameters:
	 *                       ['timerange' => ['start' => new DateTime(...), 'end' => new DateTime(...)]]
	 * @param int|null $limit - limit number of search results
	 * @param int|null $offset - offset for paging of search results
	 * @return array an array of events/journals/todos which are arrays of key-value-pairs
	 * @since 13.0.0
	 */
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

	/**
	 * Import objects
	 *
	 * @since 32.0.0
	 *
	 * @param CalendarImportOptions $options
	 * @param callable $generator<CalendarImportOptions>: Generator<\Sabre\VObject\Component\VCalendar>
	 *
	 * @return array<string,array<string,string|array<string>>>
	 */
	public function import(CalendarImportOptions $options, callable $generator): array {
		$calendarId = $this->getKey();
		$outcome = [];
		foreach ($generator($options) as $vObject) {
			$components = $vObject->getBaseComponents();
			// determine if the object has no base component types
			if (count($components) === 0) {
				if ($options->getErrors() === 1) {
					throw new InvalidArgumentException('Error importing calendar object, discovered object with no base component types');
				}
				$outcome['nbct'] = ['outcome' => 'error', 'errors' => ['One or more objects discovered with no base component types']];
				continue;
			}
			// determine if the object has more than one base component type
			// object can have multiple base components with the same uid
			// but we need to make sure they are of the same type
			if (count($components) > 1) {
				$type = $components[0]->name;
				foreach ($components as $entry) {
					if ($type !== $entry->name) {
						if ($options->getErrors() === 1) {
							throw new InvalidArgumentException('Error importing calendar object, discovered object with multiple base component types');
						}
						$outcome['mbct'] = ['outcome' => 'error', 'errors' => ['One or more objects discovered with multiple base component types']];
						continue 2;
					}
				}
			}
			// determine if the object has a uid
			if (!isset($components[0]->UID)) {
				if ($options->getErrors() === 1) {
					throw new InvalidArgumentException('Error importing calendar object, discovered object without a UID');
				}
				$outcome['noid'] = ['outcome' => 'error', 'errors' => ['One or more objects discovered without a UID']];
				continue;
			}
			$uid = $components[0]->UID->getValue();
			// validate object
			if ($options->getValidate() !== 0) {
				$issues = $this->validateComponent($vObject, true, 3);
				if ($options->getValidate() === 1 && $issues !== []) {
					$outcome[$uid] = ['outcome' => 'error', 'errors' => $issues];
					continue;
				} elseif ($options->getValidate() === 2 && $issues !== []) {
					throw new InvalidArgumentException('Error importing calendar object <' . $uid . '>, ' . $issues[0]);
				}
			}
			// create or update object in the data store
			$objectId = $this->backend->getCalendarObjectByUID($this->calendarInfo['principaluri'], $uid);
			$objectData = $vObject->serialize();
			if ($objectId === null) {
				$objectId = UUIDUtil::getUUID();
				$this->backend->createCalendarObject(
					$calendarId,
					$objectId,
					$objectData
				);
				$outcome[$uid] = ['outcome' => 'created'];
			} elseif ($objectId !== null) {
				[$cid, $oid] = explode('/', $objectId);
				if ($options->getSupersede()) {
					$this->backend->updateCalendarObject(
						$calendarId,
						$oid,
						$objectData
					);
					$outcome[$uid] = ['outcome' => 'updated'];
				} else {
					$outcome[$uid] = ['outcome' => 'exists'];
				}
			}
		}

		return $outcome;
	}

	/**
	 * Validate a component
	 *
	 * @param VCalendar $vObject
	 * @param bool $repair attempt to repair the component
	 * @param int $level minimum level of issues to return
	 * @return list<mixed>
	 */
	private function validateComponent(VCalendar $vObject, bool $repair, int $level): array {
		// validate component(S)
		$issues = $vObject->validate(Node::PROFILE_CALDAV);
		// attempt to repair
		if ($repair && count($issues) > 0) {
			$issues = $vObject->validate(Node::REPAIR);
		}
		// filter out messages based on level
		$result = [];
		foreach ($issues as $key => $issue) {
			if (isset($issue['level']) && $issue['level'] >= $level) {
				$result[] = $issue['message'];
			}
		}
		
		return $result;
	}

}
