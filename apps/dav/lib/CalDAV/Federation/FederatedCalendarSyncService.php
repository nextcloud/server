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
	private const SYNC_TOKEN_PREFIX = 'http://sabre.io/ns/sync/';

	public function __construct(
		private readonly FederatedCalendarMapper $federatedCalendarMapper,
		private readonly LoggerInterface $logger,
		private readonly CalDavSyncService $syncService,
		private readonly ICloudIdManager $cloudIdManager,
	) {
	}

	/**
	 * @return int Downloaded event count (created or updated).
	 *
	 * @throws ClientExceptionInterface If syncing the calendar fails.
	 */
	public function syncOne(FederatedCalendarEntity $calendar): int {
		[,, $sharedWith] = explode('/', $calendar->getPrincipaluri());
		$calDavUser = $this->cloudIdManager->getCloudId($sharedWith, null)->getId();
		$remoteUrl = $calendar->getRemoteUrl();
		$syncToken = $calendar->getSyncTokenForSabre();

		// Need to encode the cloud id as it might contain a colon which is not allowed in basic
		// auth according to RFC 7617
		$calDavUser = base64_encode($calDavUser);

		$syncResponse = $this->syncService->syncRemoteCalendar(
			$remoteUrl,
			$calDavUser,
			$calendar->getToken(),
			$syncToken,
			$calendar,
		);

		$newSyncToken = $syncResponse->getSyncToken();

		// Check sync token format and extract the actual sync token integer
		$matches = [];
		if (!preg_match('/^http:\/\/sabre\.io\/ns\/sync\/([0-9]+)$/', $newSyncToken, $matches)) {
			$this->logger->error("Failed to sync federated calendar at $remoteUrl: New sync token has unexpected format: $newSyncToken", [
				'calendar' => $calendar->toCalendarInfo(),
				'newSyncToken' => $newSyncToken,
			]);
			return 0;
		}

		$newSyncToken = (int)$matches[1];
		if ($newSyncToken !== $calendar->getSyncToken()) {
			$this->federatedCalendarMapper->updateSyncTokenAndTime(
				$calendar->getId(),
				$newSyncToken,
			);
		} else {
			$this->logger->debug("Sync Token for $remoteUrl unchanged from previous sync");
			$this->federatedCalendarMapper->updateSyncTime($calendar->getId());
		}

		return $syncResponse->getDownloadedEvents();
	}
}
