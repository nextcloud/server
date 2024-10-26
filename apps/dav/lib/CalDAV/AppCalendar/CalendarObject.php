<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\AppCalendar;

use OCP\Calendar\ICalendar;
use OCP\Calendar\ICreateFromString;
use OCP\Constants;
use Sabre\CalDAV\ICalendarObject;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAVACL\IACL;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Property\ICalendar\DateTime;

class CalendarObject implements ICalendarObject, IACL {
	public function __construct(
		private AppCalendar $calendar,
		private ICalendar|ICreateFromString $backend,
		private VCalendar $vobject,
	) {
	}

	public function getOwner() {
		return $this->calendar->getOwner();
	}

	public function getGroup() {
		return $this->calendar->getGroup();
	}

	public function getACL(): array {
		$acl = [
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner(),
				'protected' => true,
			]
		];
		if ($this->calendar->getPermissions() & Constants::PERMISSION_UPDATE) {
			$acl[] = [
				'privilege' => '{DAV:}write-content',
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
		return null;
	}

	public function put($data): void {
		if ($this->backend instanceof ICreateFromString && $this->calendar->getPermissions() & Constants::PERMISSION_UPDATE) {
			if (is_resource($data)) {
				$data = stream_get_contents($data) ?: '';
			}
			$this->backend->createFromString($this->getName(), $data);
		} else {
			throw new Forbidden('This calendar-object is read-only');
		}
	}

	public function get(): string {
		return $this->vobject->serialize();
	}

	public function getContentType(): string {
		return 'text/calendar; charset=utf-8';
	}

	public function getETag(): ?string {
		return null;
	}

	public function getSize() {
		return mb_strlen($this->vobject->serialize());
	}

	public function delete(): void {
		if ($this->backend instanceof ICreateFromString && $this->calendar->getPermissions() & Constants::PERMISSION_DELETE) {
			/** @var \Sabre\VObject\Component[] */
			$components = $this->vobject->getBaseComponents();
			foreach ($components as $key => $component) {
				$components[$key]->STATUS = 'CANCELLED';
				$components[$key]->SEQUENCE = isset($component->SEQUENCE) ? ((int)$component->SEQUENCE->getValue()) + 1 : 1;
				if ($component->name === 'VEVENT') {
					$components[$key]->METHOD = 'CANCEL';
				}
			}
			$this->backend->createFromString($this->getName(), (new VCalendar($components))->serialize());
		} else {
			throw new Forbidden('This calendar-object is read-only');
		}
	}

	public function getName(): string {
		// Every object is required to have an UID
		$base = $this->vobject->getBaseComponent();
		// This should never happen except the app provides invalid calendars (VEvent, VTodo... all require UID to be present)
		if ($base === null) {
			throw new NotFound('Invalid node');
		}
		if (isset($base->{'X-FILENAME'})) {
			return (string)$base->{'X-FILENAME'};
		}
		return (string)$base->UID . '.ics';
	}

	public function setName($name): void {
		throw new Forbidden('This calendar-object is read-only');
	}

	public function getLastModified(): ?int {
		$base = $this->vobject->getBaseComponent();
		if ($base !== null && $this->vobject->getBaseComponent()->{'LAST-MODIFIED'}) {
			/** @var DateTime */
			$lastModified = $this->vobject->getBaseComponent()->{'LAST-MODIFIED'};
			return $lastModified->getDateTime()->getTimestamp();
		}
		return null;
	}
}
