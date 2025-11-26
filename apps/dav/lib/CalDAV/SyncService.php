<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use OCA\DAV\CalDAV\Federation\FederatedCalendarEntity;
use OCA\DAV\CalDAV\Federation\FederatedCalendarMapper;
use OCA\DAV\Service\ASyncService;
use OCP\AppFramework\Db\TTransactional;
use OCP\AppFramework\Http;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

class SyncService extends ASyncService {
	use TTransactional;

	public function __construct(
		IClientService $clientService,
		IConfig $config,
		private readonly CalDavBackend $backend,
		private readonly FederatedCalendarMapper $federatedCalendarMapper,
		private readonly IDBConnection $dbConnection,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($clientService, $config);
	}

	/**
	 * @param string $url
	 * @param string $username
	 * @param string $sharedSecret
	 * @param string|null $syncToken
	 * @param FederatedCalendarEntity $calendar
	 */
	public function syncRemoteCalendar(
		string $url,
		string $username,
		string $sharedSecret,
		?string $syncToken,
		FederatedCalendarEntity $calendar,
	): SyncServiceResult {
		try {
			$response = $this->requestSyncReport($url, $username, $sharedSecret, $syncToken);
		} catch (ClientExceptionInterface $ex) {
			if ($ex->getCode() === Http::STATUS_UNAUTHORIZED) {
				// Remote server revoked access to the calendar => remove it
				$this->federatedCalendarMapper->delete($calendar);
				$this->logger->error("Authorization failed, remove federated calendar: $url", [
					'app' => 'dav',
				]);
				throw $ex;
			}
			$this->logger->error('Client exception:', ['app' => 'dav', 'exception' => $ex]);
			throw $ex;
		}

		// TODO: use multi-get for download
		$downloadedEvents = 0;
		foreach ($response['response'] as $resource => $status) {
			$objectUri = basename($resource);
			if (isset($status[200])) {
				$absoluteUrl = $this->prepareUri($url, $resource);
				$vCard = $this->download($absoluteUrl, $username, $sharedSecret);
				$this->atomic(function () use ($calendar, $objectUri, $vCard): void {
					$existingObject = $this->backend->getCalendarObject($calendar->getId(), $objectUri, CalDavBackend::CALENDAR_TYPE_FEDERATED);
					if (!$existingObject) {
						$this->backend->createCalendarObject($calendar->getId(), $objectUri, $vCard, CalDavBackend::CALENDAR_TYPE_FEDERATED);
					} else {
						$this->backend->updateCalendarObject($calendar->getId(), $objectUri, $vCard, CalDavBackend::CALENDAR_TYPE_FEDERATED);
					}
				}, $this->dbConnection);
				$downloadedEvents++;
			} else {
				$this->backend->deleteCalendarObject($calendar->getId(), $objectUri, CalDavBackend::CALENDAR_TYPE_FEDERATED, true);
			}
		}

		return new SyncServiceResult(
			$response['token'],
			$downloadedEvents,
		);
	}
}
