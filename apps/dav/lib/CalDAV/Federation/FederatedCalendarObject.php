<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use Sabre\CalDAV\ICalendarObject;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAVACL\IACL;

class FederatedCalendarObject implements ICalendarObject, IACL {

	public function __construct(
		protected FederatedCalendar $calendarObject,
		protected $objectData,
	) {
	}

	public function getName(): string {
		return $this->objectData['uri'];
	}

	/**
	 * @param string $name Name of the file
	 */
	public function setName($name) {
		throw new \Exception('Not implemented');
	}

	public function get(): string {
		return $this->objectData['calendardata'];
	}

	/**
	 * @param resource|string $data contents of the file
	 */
	public function put($data): string {

		$etag = $this->calendarObject->updateFile($this->objectData['uri'], $data);
		$this->objectData['calendardata'] = $data;
		$this->objectData['etag'] = $etag;

		return $etag;
	}

	public function delete(): void {
		$this->calendarObject->deleteFile($this->objectData['uri']);
	}

	public function getContentType(): ?string {
		$mime = 'text/calendar; charset=utf-8';
		if (isset($this->objectData['component']) && $this->objectData['component']) {
			$mime .= '; component=' . $this->objectData['component'];
		}

		return $mime;
	}

	public function getETag(): string {
		if (isset($this->objectData['etag'])) {
			return $this->objectData['etag'];
		} else {
			return '"' . md5($this->get()) . '"';
		}
	}

	public function getLastModified(): int {
		return $this->objectData['lastmodified'];
	}

	public function getSize(): int {
		if (isset($this->objectData['size'])) {
			return $this->objectData['size'];
		} else {
			return strlen($this->get());
		}
	}

	public function getOwner(): ?string {
		return $this->calendarObject->getPrincipalURI();
	}

	public function getGroup(): ?string {
		return null;
	}

	/**
	 * @return array<array-key, mixed>
	 */
	public function getACL(): array {
		return $this->calendarObject->getACL();
	}

	public function setACL(array $acl): void {
		throw new MethodNotAllowed('Changing ACLs on federated events is not allowed');
	}

	public function getSupportedPrivilegeSet(): ?array {
		return null;
	}

}
