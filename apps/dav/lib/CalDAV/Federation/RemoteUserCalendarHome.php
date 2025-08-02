<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\CalDAV\Calendar;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Sabre\CalDAV\Backend;
use Sabre\CalDAV\CalendarHome;
use Sabre\DAV\Exception\NotFound;

class RemoteUserCalendarHome extends CalendarHome {
	public function __construct(
		Backend\BackendInterface $caldavBackend,
		$principalInfo,
		private readonly IL10N $l10n,
		private readonly IConfig $config,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($caldavBackend, $principalInfo);
	}

	public function getChild($name) {
		// Remote users can only have incoming shared calendars so we can skip the rest of a regular
		// calendar home
		foreach ($this->caldavBackend->getCalendarsForUser($this->principalInfo['uri']) as $calendar) {
			if ($calendar['uri'] === $name) {
				return new Calendar(
					$this->caldavBackend,
					$calendar,
					$this->l10n,
					$this->config,
					$this->logger,
				);
			}
		}

		throw new NotFound("Node with name $name could not be found");
	}

	public function getChildren(): array {
		$objects = [];

		// Remote users can only have incoming shared calendars so we can skip the rest of a regular
		// calendar home
		$calendars = $this->caldavBackend->getCalendarsForUser($this->principalInfo['uri']);
		foreach ($calendars as $calendar) {
			$objects[] = new Calendar(
				$this->caldavBackend,
				$calendar,
				$this->l10n,
				$this->config,
				$this->logger,
			);
		}

		return $objects;
	}
}
