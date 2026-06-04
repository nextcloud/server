<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Trashbin;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\IRestorable;
use OCA\DAV\DAV\Sharing\Backend;
use Sabre\CalDAV\ICalendarObject;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;

class DeletedCalendarObject implements IACL, ICalendarObject, IRestorable {
	use ACLTrait;

	public function __construct(
		private string $name,
		/** @var mixed[] */
		private array $objectData,
		private string $principalUri,
		private CalDavBackend $calDavBackend,
	) {
	}

	#[\Override]
	public function delete() {
		if (!$this->canModify()) {
			throw new Forbidden('Read-only sharees cannot permanently delete trashbin entries');
		}
		$this->calDavBackend->deleteCalendarObject(
			$this->objectData['calendarid'],
			$this->objectData['uri'],
			CalDavBackend::CALENDAR_TYPE_CALENDAR,
			true
		);
	}

	private function isShared(): bool {
		$calendarOwner = $this->objectData['calendarprincipaluri'] ?? null;
		return $calendarOwner !== null && $calendarOwner !== $this->principalUri;
	}

	private function canModify(): bool {
		if (!$this->isShared()) {
			return true;
		}
		// For shared entries, only write sharees may delete/restore.
		return ($this->objectData['shared_access'] ?? null) === Backend::ACCESS_READ_WRITE;
	}

	#[\Override]
	public function getName() {
		return $this->name;
	}

	#[\Override]
	public function setName($name) {
		throw new Forbidden();
	}

	#[\Override]
	public function getLastModified() {
		return 0;
	}

	#[\Override]
	public function put($data) {
		throw new Forbidden();
	}

	#[\Override]
	public function get() {
		return $this->objectData['calendardata'];
	}

	#[\Override]
	public function getContentType() {
		$mime = 'text/calendar; charset=utf-8';
		if (isset($this->objectData['component']) && $this->objectData['component']) {
			$mime .= '; component=' . $this->objectData['component'];
		}

		return $mime;
	}

	#[\Override]
	public function getETag() {
		return $this->objectData['etag'];
	}

	#[\Override]
	public function getSize() {
		return (int)$this->objectData['size'];
	}

	#[\Override]
	public function restore(): void {
		if (!$this->canModify()) {
			throw new Forbidden('Read-only sharees cannot restore trashbin entries');
		}
		$this->calDavBackend->restoreCalendarObject($this->objectData);
	}

	public function getDeletedAt(): ?int {
		return $this->objectData['deleted_at'] ? (int)$this->objectData['deleted_at'] : null;
	}

	public function getCalendarUri(): string {
		return $this->objectData['calendaruri'];
	}

	public function getSourceCalendarUri(): string {
		return $this->objectData['sourcecalendaruri'] ?? $this->objectData['calendaruri'];
	}

	public function getCalendarPrincipalUri(): ?string {
		return $this->objectData['calendarprincipaluri'] ?? null;
	}

	public function getDelegator(): ?string {
		return $this->objectData['delegator'] ?? null;
	}

	#[\Override]
	public function getACL(): array {
		$acl = [
			[
				'privilege' => '{DAV:}read', // For queries
				'principal' => $this->getOwner(),
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner() . '/calendar-proxy-write',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner() . '/calendar-proxy-read',
				'protected' => true,
			],
		];

		if ($this->canModify()) {
			$acl[] = [
				'privilege' => '{DAV:}unbind',
				'principal' => $this->getOwner(),
				'protected' => true,
			];

			$acl[] = [
				'privilege' => '{DAV:}unbind',
				'principal' => $this->getOwner() . '/calendar-proxy-write',
				'protected' => true,
			];

		}

		return $acl;
	}

	#[\Override]
	public function getOwner() {
		return $this->principalUri;
	}
}
