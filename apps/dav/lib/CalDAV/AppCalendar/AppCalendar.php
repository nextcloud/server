<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\AppCalendar;

use OCA\DAV\CalDAV\Integration\ExternalCalendar;
use OCA\DAV\CalDAV\Plugin;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICreateFromString;
use OCP\Constants;
use Sabre\CalDAV\CalendarQueryValidator;
use Sabre\CalDAV\ICalendarObject;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropPatch;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

class AppCalendar extends ExternalCalendar {
	protected ICalendar $calendar;

	public function __construct(
		string $appId,
		ICalendar $calendar,
		protected string $principal,
	) {
		parent::__construct($appId, $calendar->getUri());
		$this->calendar = $calendar;
	}

	/**
	 * Return permissions supported by the backend calendar
	 * @return int Permissions based on \OCP\Constants
	 */
	public function getPermissions(): int {
		// Make sure to only promote write support if the backend implement the correct interface
		if ($this->calendar instanceof ICreateFromString) {
			return $this->calendar->getPermissions();
		}
		return Constants::PERMISSION_READ;
	}

	public function getOwner(): ?string {
		return $this->principal;
	}

	public function getGroup(): ?string {
		return null;
	}

	public function getACL(): array {
		$acl = [
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner(),
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}write-properties',
				'principal' => $this->getOwner(),
				'protected' => true,
			]
		];
		if ($this->getPermissions() & Constants::PERMISSION_CREATE) {
			$acl[] = [
				'privilege' => '{DAV:}write',
				'principal' => $this->getOwner(),
				'protected' => true,
			];
		}
		return $acl;
	}

	public function setACL(array $acl): void {
		throw new Forbidden('Setting ACL is not supported on this node');
	}

	public function getSupportedPrivilegeSet(): ?array {
		// Use the default one
		return null;
	}

	public function getLastModified(): ?int {
		// unknown
		return null;
	}

	public function delete(): void {
		// No method for deleting a calendar in OCP\Calendar\ICalendar
		throw new Forbidden('Deleting an entry is not implemented');
	}

	public function createFile($name, $data = null) {
		if ($this->calendar instanceof ICreateFromString) {
			if (is_resource($data)) {
				$data = stream_get_contents($data) ?: null;
			}
			$this->calendar->createFromString($name, is_null($data) ? '' : $data);
			return null;
		} else {
			throw new Forbidden('Creating a new entry is not allowed');
		}
	}

	public function getProperties($properties) {
		return [
			'{DAV:}displayname' => $this->calendar->getDisplayName() ?: $this->calendar->getKey(),
			'{http://apple.com/ns/ical/}calendar-color' => $this->calendar->getDisplayColor() ?: '#0082c9',
			'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VEVENT', 'VJOURNAL', 'VTODO']),
		];
	}

	public function calendarQuery(array $filters) {
		$result = [];
		$objects = $this->getChildren();

		foreach ($objects as $object) {
			if ($this->validateFilterForObject($object, $filters)) {
				$result[] = $object->getName();
			}
		}

		return $result;
	}

	protected function validateFilterForObject(ICalendarObject $object, array $filters): bool {
		/** @var \Sabre\VObject\Component\VCalendar */
		$vObject = Reader::read($object->get());

		$validator = new CalendarQueryValidator();
		$result = $validator->validate($vObject, $filters);

		// Destroy circular references so PHP will GC the object.
		$vObject->destroy();

		return $result;
	}

	public function childExists($name): bool {
		try {
			$this->getChild($name);
			return true;
		} catch (NotFound $error) {
			return false;
		}
	}

	public function getChild($name) {
		// Try to get calendar by filename
		$children = $this->calendar->search($name, ['X-FILENAME']);
		if (count($children) === 0) {
			// If nothing found try to get by UID from filename
			$pos = strrpos($name, '.ics');
			$children = $this->calendar->search(substr($name, 0, $pos ?: null), ['UID']);
		}

		if (count($children) > 0) {
			return new CalendarObject($this, $this->calendar, new VCalendar($children));
		}

		throw new NotFound('Node not found');
	}

	/**
	 * @return ICalendarObject[]
	 */
	public function getChildren(): array {
		$objects = $this->calendar->search('');
		// We need to group by UID (actually by filename but we do not have that information)
		$result = [];
		foreach ($objects as $object) {
			$uid = (string)$object['UID'] ?: uniqid();
			if (!isset($result[$uid])) {
				$result[$uid] = [];
			}
			$result[$uid][] = $object;
		}

		return array_map(function (array $children) {
			return new CalendarObject($this, $this->calendar, new VCalendar($children));
		}, $result);
	}

	public function propPatch(PropPatch $propPatch): void {
		// no setDisplayColor or setDisplayName in \OCP\Calendar\ICalendar
	}
}
