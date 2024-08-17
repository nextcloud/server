<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CalDAV;

use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarObject;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject\Reader;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\ElementList;
use Sabre\VObject\Property;

class Plugin extends \Sabre\CalDAV\Plugin {
	public const SYSTEM_CALENDAR_ROOT = 'system-calendars';

	/**
	 * Initializes the plugin
	 *
	 * @param Server $server
	 * @return void
	 */
	public function initialize(Server $server) {

		parent::initialize($server);

		$server->on('calendarObjectChange', [$this, 'calendarObjectChange'], 90);
		
	}

	/**
	 * Returns the path to a principal's calendar home.
	 *
	 * The return url must not end with a slash.
	 * This function should return null in case a principal did not have
	 * a calendar home.
	 *
	 * @param string $principalUrl
	 * @return string|null
	 */
	public function getCalendarHomeForPrincipal($principalUrl) {
		if (strrpos($principalUrl, 'principals/users', -strlen($principalUrl)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principalUrl);
			return self::CALENDAR_ROOT . '/' . $principalId;
		}
		if (strrpos($principalUrl, 'principals/calendar-resources', -strlen($principalUrl)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principalUrl);
			return self::SYSTEM_CALENDAR_ROOT . '/calendar-resources/' . $principalId;
		}
		if (strrpos($principalUrl, 'principals/calendar-rooms', -strlen($principalUrl)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principalUrl);
			return self::SYSTEM_CALENDAR_ROOT . '/calendar-rooms/' . $principalId;
		}
	}

	/**
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @param VCalendar $vCal
	 * @param mixed $calendarPath
	 * @param mixed $modified
	 * @param mixed $isNew
	 */
	public function calendarObjectChange(RequestInterface $request, ResponseInterface $response, VCalendar $alteredObject, $calendarPath, &$modified, $isNew) {
		
		// determine if the calendar has an event
		// if there is no event there is nothing to do
		if (!$alteredObject->VEVENT) {
			return;
		}
		// determine if altered calendar event is a new
		// if calendar event is new sanitize and exit
		if ($isNew) {
			$this->sanitizeCreatedInstance($alteredObject->VEVENT, $modified);
			return;
		}
		// retrieve current calendar event node
		/** @var \OCA\DAV\CalDAV\CalendarObject $currentNode */
		$currentNode = $this->server->tree->getNodeForPath($request->getPath());
		// convert calendar event string data to VCalendar object
		/** @var \Sabre\VObject\Component\VCalendar $currentObject */
		$currentObject = Reader::read($currentNode->get());
		// find what has changed (base, recurrence, both) between altered and current calendar event
		$delta = $this->findEventInstanceDelta($alteredObject->VEVENT, $currentObject->VEVENT);
		// 
		foreach ($delta as $entry) {
			// determine if this instance was created or updated
			if ($entry['current'] !== null) {
				$this->sanitizeUpdatedInstance($entry['altered'], $entry['current'], $modified);
			} else {
				$this->sanitizeCreatedInstance($entry['altered'], $modified);
			}
		}

	}

	public function sanitizeCreatedInstance(VEvent $altered, $modified): void {
		
		// sanitize attendees
		if (isset($altered->ATTENDEE)) {
			$this->sanitizeEventAttendees($altered, $modified);
		}

	}

	public function sanitizeUpdatedInstance(VEvent $altered, VEvent $current, $modified): void {
		
		// find differences in properties
		$delta = $this->findEventPropertyDelta($altered, $current, $modified);
		// determine if any important properties have changed sanitize attendees
		if (isset($delta['DTSTART']) || isset($delta['DTEND']) || isset($delta['LOCATION']) || isset($delta['RRULE'])) {
			$this->sanitizeEventAttendees($altered, $modified);
		}

	}

	public function sanitizeEventAttendees(VEvent $event, $modified): void {
		
		// iterate thought attendees
		foreach ($event->ATTENDEE as $id => $entry) {
			// determine attendee participation status
			// if status is missing or NOT set correctly change the status
			if (!isset($entry['PARTSTAT']) || $entry['PARTSTAT']->getValue() !== 'NEEDS-ACTION') {
				$event->ATTENDEE[$id]['PARTSTAT']->setValue('NEEDS-ACTION');
				$modified = true;
			}
		}

	}

	protected function findEventInstanceDelta(VEvent $altered, VEvent $current): array {
		
		$list = [];
		// iterate through altered event instances
		foreach ($altered as $event) {
			// create instance id
			if (!isset($event->{'RECURRENCE-ID'})) {
				$id = $event->UID->getValue() . ':Base';
			} else {
				$id = $event->UID->getValue() . ':' . $event->{'RECURRENCE-ID'}->getValue();
			}
			// add instance to list
			$list[$id] = ['altered' => $event, 'current' => null];
		}
		// iterate through current event instances
		foreach ($current as $event) {
			// create instance id
			if (!isset($event->{'RECURRENCE-ID'})) {
				$id = $event->UID->getValue() . ':Base';
			} else {
				$id = $event->UID->getValue() . ':' . $event->{'RECURRENCE-ID'}->getValue();
			}
			// determine if id exists in list
			if (isset($list[$id])) {
				// compare altered instance to current instance
				if ($list[$id]['altered']->{'LAST-MODIFIED'}->getValue() == $event->{'LAST-MODIFIED'}->getValue() &&
					$list[$id]['altered']->SEQUENCE->getValue() == $event->SEQUENCE->getValue()) {
					// remove entry from list if instance has not changed
					unset($list[$id]);
				} else {
					// update entry in list with current instance 
					$list[$id]['current'] = $event;
				}
			} else {
				// add entry to list
				$list[$id] = ['altered' => null, 'current' => $event];
			}
		}

		return $list;

	}

	protected function findEventPropertyDelta(VEvent $altered, VEvent $current): array {
		
		$list = [];
		// iterate through altered event properties
		foreach ($altered->children() as $property) {
			// add property to list
			$list[$property->name] = ['altered' => $property->getValue(), 'current' => null];
		}
		// iterate through altered event properties
		foreach ($current->children() as $property) {
			if (isset($list[$property->name])) {
				if ($list[$property->name]['altered'] == $property->getValue()) {
					// remove entry from list if instance has not changed
					unset($list[$property->name]);
				} else {
					// update entry in list with current instance 
					$list[$property->name]['current'] = $property->getValue();
				}
			} else {
				// add entry to list
				$list[$property->name] = ['altered' => null, 'current' => $property->getValue()];
			}
		}
		
		return $list;
	}

}
