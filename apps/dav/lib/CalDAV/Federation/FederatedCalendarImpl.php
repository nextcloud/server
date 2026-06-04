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

	#[\Override]
	public function getKey(): string {
		return (string)$this->calendarInfo['id'];
	}

	#[\Override]
	public function getUri(): string {
		return $this->calendarInfo['uri'];
	}

	#[\Override]
	public function getDisplayName(): ?string {
		return $this->calendarInfo['{DAV:}displayname'];
	}

	#[\Override]
	public function getDisplayColor(): ?string {
		return $this->calendarInfo['{http://apple.com/ns/ical/}calendar-color'];
	}

	#[\Override]
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

	#[\Override]
	public function getPermissions(): int {
		return $this->calendarInfo['{http://owncloud.org/ns}permissions'] ?? Constants::PERMISSION_READ;
	}

	#[\Override]
	public function isDeleted(): bool {
		return false;
	}

	#[\Override]
	public function isShared(): bool {
		return true;
	}

	#[\Override]
	public function isWritable(): bool {
		$permissions = $this->getPermissions();
		return ($permissions & Constants::PERMISSION_UPDATE) !== 0;
	}

	#[\Override]
	public function isEnabled(): bool {
		return $this->calendarInfo['{http://owncloud.org/ns}calendar-enabled'] ?? true;
	}
}
