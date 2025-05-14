<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\CalDAV\SyncService as CalDavSyncService;
use OCP\Federation\ICloudIdManager;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

class FederatedCalendarSyncService {
	public function __construct(
		private readonly FederatedCalendarMapper $federatedCalendarMapper,
		private readonly LoggerInterface $logger,
		private readonly CalDavSyncService $syncService,
		private readonly ICloudIdManager $cloudIdManager,
	) {
	}

	/**
	 * @throws ClientExceptionInterface If syncing the calendar fails.
	 */
	public function syncOne(FederatedCalendarEntity $calendar): void {
		[,, $sharedWith] = explode('/', $calendar->getPrincipaluri());
		$calDavUser = $this->cloudIdManager->getCloudId($sharedWith, null)->getId();
		$remoteUrl = $calendar->getRemoteUrl();
		$syncToken = $calendar->getSyncTokenForSabre();

		$newSyncToken = $this->syncService->syncRemoteCalendar(
			$remoteUrl,
			$calDavUser,
			$calendar->getToken(),
			$syncToken,
			$calendar,
		);

		$newSyncToken = (int)substr($newSyncToken, strlen('http://sabre.io/ns/sync/'));
		if ($newSyncToken !== $calendar->getSyncToken()) {
			$this->federatedCalendarMapper->updateSyncTokenAndTime(
				$calendar->getId(),
				$newSyncToken,
			);
		} else {
			$this->logger->debug("Sync Token for $remoteUrl unchanged from previous sync");
			$this->federatedCalendarMapper->updateSyncTime($calendar->getId());
		}
	}
}
