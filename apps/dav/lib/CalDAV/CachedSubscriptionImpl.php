<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarIsEnabled;
use OCP\Calendar\ICalendarIsShared;
use OCP\Calendar\ICalendarIsWritable;
use OCP\Constants;

class CachedSubscriptionImpl implements ICalendar, ICalendarIsEnabled, ICalendarIsShared, ICalendarIsWritable {

	public function __construct(
		private CachedSubscription $calendar,
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

	public function search(string $pattern, array $searchProperties = [], array $options = [], $limit = null, $offset = null): array {
		return $this->backend->search($this->calendarInfo, $pattern, $searchProperties, $options, $limit, $offset);
	}

	/**
	 * @return int build up using \OCP\Constants
	 * @since 13.0.0
	 */
	public function getPermissions(): int {
		$permissions = $this->calendar->getACL();
		$result = 0;
		foreach ($permissions as $permission) {
			switch ($permission['privilege']) {
				case '{DAV:}read':
					$result |= Constants::PERMISSION_READ;
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

	public function isWritable(): bool {
		return false;
	}

	public function isDeleted(): bool {
		return false;
	}

	public function isShared(): bool {
		return true;
	}

	public function getSource(): string {
		return $this->calendarInfo['source'];
	}
}
