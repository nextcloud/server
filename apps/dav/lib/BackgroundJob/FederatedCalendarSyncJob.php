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
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

class FederatedCalendarSyncJob extends QueuedJob {
	public const ARGUMENT_ID = 'id';

	public function __construct(
		ITimeFactory $time,
		private readonly FederatedCalendarSyncService $syncService,
		private readonly FederatedCalendarMapper $federatedCalendarMapper,
		private readonly CalendarFederationConfig $calendarFederationConfig,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);

		$this->setAllowParallelRuns(false);
	}

	protected function run($argument): void {
		if (!$this->calendarFederationConfig->isFederationEnabled()) {
			return;
		}

		$id = $argument[self::ARGUMENT_ID] ?? null;
		if (!is_numeric($id)) {
			return;
		}

		$id = (int)$id;
		try {
			$calendar = $this->federatedCalendarMapper->find($id);
		} catch (DoesNotExistException $e) {
			return;
		}

		try {
			$this->syncService->syncOne($calendar);
		} catch (ClientExceptionInterface $e) {
			$name = $calendar->getUri();
			$this->logger->error("Failed to sync federated calendar $name: " . $e->getMessage(), [
				'exception' => $e,
				'calendar' => $calendar->toCalendarInfo(),
			]);

			// Let the periodic background job pick up the calendar at a later point
			$calendar->setLastSync(1);
			$this->federatedCalendarMapper->update($calendar);
		}
	}
}
