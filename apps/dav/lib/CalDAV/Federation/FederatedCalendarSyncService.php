<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Service\ASyncService;
use OCP\AppFramework\Db\TTransactional;
use OCP\AppFramework\Http;
use OCP\Federation\ICloudIdManager;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

class FederatedCalendarSyncService extends ASyncService {
	use TTransactional;

	private const SYNC_TOKEN_PREFIX = 'http://sabre.io/ns/sync/';

	public function __construct(
		IClientService $clientService,
		IConfig $config,
		private readonly FederatedCalendarMapper $federatedCalendarMapper,
		private readonly LoggerInterface $logger,
		private readonly CalDavBackend $backend,
		private readonly IDBConnection $dbConnection,
		private readonly ICloudIdManager $cloudIdManager,
	) {
		parent::__construct($clientService, $config);
	}

	/**
	 * Extract and encode credentials from a federated calendar entity.
	 *
	 * @return array{username: string, remoteUrl: string, token: string}
	 */
	private function getCredentials(FederatedCalendarEntity $calendar): array {
		[,, $sharedWith] = explode('/', $calendar->getPrincipaluri());
		$calDavUser = $this->cloudIdManager->getCloudId($sharedWith, null)->getId();

		// Need to encode the cloud id as it might contain a colon which is not allowed in basic
		// auth according to RFC 7617
		$calDavUser = base64_encode($calDavUser);

		return [
			'username' => $calDavUser,
			'remoteUrl' => $calendar->getRemoteUrl(),
			'token' => $calendar->getToken(),
		];
	}

	/**
	 * @return int Downloaded event count (created or updated).
	 *
	 * @throws ClientExceptionInterface If syncing the calendar fails.
	 */
	public function syncOne(FederatedCalendarEntity $calendar): int {
		$credentials = $this->getCredentials($calendar);
		$syncToken = $calendar->getSyncTokenForSabre();

		try {
			$response = $this->requestSyncReport(
				$credentials['remoteUrl'],
				$credentials['username'],
				$credentials['token'],
				$syncToken,
			);
		} catch (ClientExceptionInterface $ex) {
			if ($ex->getCode() === Http::STATUS_UNAUTHORIZED) {
				// Remote server revoked access to the calendar => remove it
				$this->federatedCalendarMapper->delete($calendar);
				$this->logger->warning("Authorization failed, remove federated calendar: {$credentials['remoteUrl']}", [
					'app' => 'dav',
				]);
				return 0;
			}
			$this->logger->error('Client exception:', ['app' => 'dav', 'exception' => $ex]);
			throw $ex;
		}

		// Process changes from remote
		$downloadedEvents = 0;
		foreach ($response['response'] as $resource => $status) {
			$objectUri = basename($resource);
			if (isset($status[200])) {
				// Object created or updated
				$absoluteUrl = $this->prepareUri($credentials['remoteUrl'], $resource);
				$calendarData = $this->download($absoluteUrl, $credentials['username'], $credentials['token']);
				$this->atomic(function () use ($calendar, $objectUri, $calendarData): void {
					$existingObject = $this->backend->getCalendarObject(
						$calendar->getId(),
						$objectUri,
						CalDavBackend::CALENDAR_TYPE_FEDERATED
					);
					if (!$existingObject) {
						$this->backend->createCalendarObject(
							$calendar->getId(),
							$objectUri,
							$calendarData,
							CalDavBackend::CALENDAR_TYPE_FEDERATED
						);
					} else {
						$this->backend->updateCalendarObject(
							$calendar->getId(),
							$objectUri,
							$calendarData,
							CalDavBackend::CALENDAR_TYPE_FEDERATED
						);
					}
				}, $this->dbConnection);
				$downloadedEvents++;
			} else {
				// Object deleted
				$this->backend->deleteCalendarObject(
					$calendar->getId(),
					$objectUri,
					CalDavBackend::CALENDAR_TYPE_FEDERATED,
					true
				);
			}
		}

		$newSyncToken = $response['token'];

		// Check sync token format and extract the actual sync token integer
		$matches = [];
		if (!preg_match('/^http:\/\/sabre\.io\/ns\/sync\/([0-9]+)$/', $newSyncToken, $matches)) {
			$this->logger->error("Failed to sync federated calendar at {$credentials['remoteUrl']}: New sync token has unexpected format: $newSyncToken", [
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
			$this->logger->debug("Sync Token for {$credentials['remoteUrl']} unchanged from previous sync");
			$this->federatedCalendarMapper->updateSyncTime($calendar->getId());
		}

		return $downloadedEvents;
	}

	/**
	 * Create a calendar object on the remote server.
	 *
	 * @throws ClientExceptionInterface If the remote request fails.
	 */
	public function createCalendarObject(FederatedCalendarEntity $calendar, string $name, string $data): string {
		$credentials = $this->getCredentials($calendar);
		$objectUrl = $this->prepareUri($credentials['remoteUrl'], $name);

		return $this->requestPut(
			$objectUrl,
			$credentials['username'],
			$credentials['token'],
			$data,
			'text/calendar; charset=utf-8'
		);
	}

	/**
	 * Update a calendar object on the remote server.
	 *
	 * @throws ClientExceptionInterface If the remote request fails.
	 */
	public function updateCalendarObject(FederatedCalendarEntity $calendar, string $name, string $data): string {
		$credentials = $this->getCredentials($calendar);
		$objectUrl = $this->prepareUri($credentials['remoteUrl'], $name);

		return $this->requestPut(
			$objectUrl,
			$credentials['username'],
			$credentials['token'],
			$data,
			'text/calendar; charset=utf-8'
		);
	}

	/**
	 * Delete a calendar object on the remote server.
	 *
	 * @throws ClientExceptionInterface If the remote request fails.
	 */
	public function deleteCalendarObject(FederatedCalendarEntity $calendar, string $name): void {
		$credentials = $this->getCredentials($calendar);
		$objectUrl = $this->prepareUri($credentials['remoteUrl'], $name);

		$this->requestDelete($objectUrl, $credentials['username'], $credentials['token']);
	}
}
