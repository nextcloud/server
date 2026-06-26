<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use OCA\DAV\AppInfo\Application;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use function max;

class RetentionService {
	public const RETENTION_CONFIG_KEY = 'calendarRetentionObligation';
	private const DEFAULT_RETENTION_SECONDS = 30 * 24 * 60 * 60;

	public function __construct(
		private IConfig $config,
		private ITimeFactory $time,
		private CalDavBackend $calDavBackend,
	) {
	}

	public function getDuration(): int {
		return max(
			(int)$this->config->getAppValue(
				Application::APP_ID,
				self::RETENTION_CONFIG_KEY,
				(string)self::DEFAULT_RETENTION_SECONDS
			),
			0 // Just making sure we don't delete things in the future when a negative number is passed
		);
	}

	public function cleanUp(): void {
		$retentionTime = $this->getDuration();
		$now = $this->time->getTime();

		$calendars = $this->calDavBackend->getDeletedCalendars($now - $retentionTime);
		foreach ($calendars as $calendar) {
			$this->calDavBackend->deleteCalendar($calendar['id'], true);
		}

		$objects = $this->calDavBackend->getDeletedCalendarObjects($now - $retentionTime);
		foreach ($objects as $object) {
			$this->calDavBackend->deleteCalendarObject(
				$object['calendarid'],
				$object['uri'],
				$object['calendartype'],
				true
			);
		}
	}
}
