<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CalDAV\Federation\CalendarFederationConfig;
use OCA\DAV\CalDAV\Federation\FederatedCalendarMapper;
use OCA\DAV\CalDAV\Federation\FederatedCalendarSyncService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

class FederatedCalendarPeriodicSyncJob extends TimedJob {
	private const DOWNLOAD_LIMIT = 500;

	public function __construct(
		ITimeFactory $time,
		private readonly FederatedCalendarSyncService $syncService,
		private readonly FederatedCalendarMapper $federatedCalendarMapper,
		private readonly CalendarFederationConfig $calendarFederationConfig,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);

		$this->setTimeSensitivity(self::TIME_SENSITIVE);
		$this->setAllowParallelRuns(false);
		$this->setInterval(3600);
	}

	protected function run($argument): void {
		if (!$this->calendarFederationConfig->isFederationEnabled()) {
			return;
		}

		$downloadedEvents = 0;
		$oneHourAgo = $this->time->getTime() - 3600;
		$calendars = $this->federatedCalendarMapper->findUnsyncedSinceBefore($oneHourAgo);
		foreach ($calendars as $calendar) {
			try {
				$downloadedEvents += $this->syncService->syncOne($calendar);
			} catch (ClientExceptionInterface $e) {
				$name = $calendar->getUri();
				$this->logger->error("Failed to sync federated calendar $name: " . $e->getMessage(), [
					'exception' => $e,
					'calendar' => $calendar->toCalendarInfo(),
				]);
			}

			// Prevent stalling the background job queue for too long
			if ($downloadedEvents >= self::DOWNLOAD_LIMIT) {
				break;
			}
		}
	}
}
