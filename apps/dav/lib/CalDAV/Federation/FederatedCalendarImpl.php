<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarIsEnabled;
use OCP\Calendar\ICalendarIsShared;
use OCP\Calendar\ICalendarIsWritable;
use OCP\Constants;

class FederatedCalendarImpl implements ICalendar, ICalendarIsShared, ICalendarIsWritable, ICalendarIsEnabled {
	public function __construct(
		private readonly array $calendarInfo,
		private readonly CalDavBackend $calDavBackend,
	) {
	}

	public function getKey(): string {
		return (string)$this->calendarInfo['id'];
	}

	public function getUri(): string {
		return $this->calendarInfo['uri'];
	}

	public function getDisplayName(): ?string {
		return $this->calendarInfo['{DAV:}displayname'];
	}

	public function getDisplayColor(): ?string {
		return $this->calendarInfo['{http://apple.com/ns/ical/}calendar-color'];
	}

	public function search(string $pattern, array $searchProperties = [], array $options = [], ?int $limit = null, ?int $offset = null): array {
		return $this->calDavBackend->search(
			$this->calendarInfo,
			$pattern,
			$searchProperties,
			$options,
			$limit,
			$offset,
		);
	}

	public function getPermissions(): int {
		// TODO: implement read-write sharing
		return Constants::PERMISSION_READ;
	}

	public function isDeleted(): bool {
		return false;
	}

	public function isShared(): bool {
		return true;
	}

	public function isWritable(): bool {
		return false;
	}

	public function isEnabled(): bool {
		return $this->calendarInfo['{http://owncloud.org/ns}calendar-enabled'] ?? true;
	}
}
