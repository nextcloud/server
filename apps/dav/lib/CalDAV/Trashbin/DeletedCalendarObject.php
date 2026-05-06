<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Trashbin;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\IRestorable;
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
		$this->calDavBackend->deleteCalendarObject(
			$this->objectData['calendarid'],
			$this->objectData['uri'],
			CalDavBackend::CALENDAR_TYPE_CALENDAR,
			true
		);
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
		$this->calDavBackend->restoreCalendarObject($this->objectData);
	}

	public function getDeletedAt(): ?int {
		return $this->objectData['deleted_at'] ? (int)$this->objectData['deleted_at'] : null;
	}

	public function getCalendarUri(): string {
		return $this->objectData['calendaruri'];
	}

	#[\Override]
	public function getACL(): array {
		return [
			[
				'privilege' => '{DAV:}read', // For queries
				'principal' => $this->getOwner(),
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}unbind', // For moving and deletion
				'principal' => '{DAV:}owner',
				'protected' => true,
			],
		];
	}

	#[\Override]
	public function getOwner() {
		return $this->principalUri;
	}
}
