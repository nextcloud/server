<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Listener;

use OCA\DAV\CalDAV\Federation\CalendarFederationNotifier;
use OCA\DAV\DAV\RemoteUserPrincipalBackend;
use OCA\DAV\DAV\Sharing\SharingMapper;
use OCP\Calendar\Events\CalendarObjectCreatedEvent;
use OCP\Calendar\Events\CalendarObjectDeletedEvent;
use OCP\Calendar\Events\CalendarObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Federation\ICloudIdManager;
use OCP\OCM\Exceptions\OCMProviderException;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event|CalendarObjectCreatedEvent|CalendarObjectUpdatedEvent|CalendarObjectDeletedEvent>
 */
class CalendarFederationNotificationListener implements IEventListener {
	public function __construct(
		private readonly ICloudIdManager $cloudIdManager,
		private readonly CalendarFederationNotifier $calendarFederationNotifier,
		private readonly LoggerInterface $logger,
		private readonly SharingMapper $sharingMapper,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof CalendarObjectCreatedEvent)
			&& !($event instanceof CalendarObjectUpdatedEvent)
			&& !($event instanceof CalendarObjectDeletedEvent)
		) {
			return;
		}

		$remoteUserShares = array_filter($event->getShares(), function (array $share): bool {
			$sharedWithPrincipal = $share['{http://owncloud.org/ns}principal'] ?? '';
			[$prefix] = \Sabre\Uri\split($sharedWithPrincipal);
			return $prefix === RemoteUserPrincipalBackend::PRINCIPAL_PREFIX;
		});
		if (empty($remoteUserShares)) {
			// Not shared with any remote user
			return;
		}

		$calendarInfo = $event->getCalendarData();
		$remoteUserPrincipals = array_map(
			static fn (array $share) => $share['{http://owncloud.org/ns}principal'],
			$remoteUserShares,
		);
		$remoteShares = $this->sharingMapper->getSharesByPrincipalsAndResource(
			$remoteUserPrincipals,
			(int)$calendarInfo['id'],
			'calendar',
		);

		foreach ($remoteShares as $share) {
			[, $name] = \Sabre\Uri\split($share['principaluri']);
			$shareWithRaw = base64_decode($name);
			try {
				$shareWith = $this->cloudIdManager->resolveCloudId($shareWithRaw);
			} catch (\InvalidArgumentException $e) {
				// Not a valid remote user principal
				continue;
			}

			[, $sharedByUid] = \Sabre\Uri\split($calendarInfo['principaluri']);

			$remoteUrl = $shareWith->getRemote();
			try {
				$response = $this->calendarFederationNotifier->notifySyncCalendar(
					$shareWith,
					$sharedByUid,
					$calendarInfo['uri'],
					$share['token'],
				);
			} catch (OCMProviderException $e) {
				$this->logger->error("Failed to send SYNC_CALENDAR notification to remote $remoteUrl", [
					'exception' => $e,
					'shareWith' => $shareWith->getId(),
					'calendarName' => $calendarInfo['uri'],
					'calendarOwner' => $sharedByUid,
				]);
				continue;
			}

			if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
				$this->logger->error("Remote $remoteUrl rejected SYNC_CALENDAR notification", [
					'statusCode' => $response->getStatusCode(),
					'shareWith' => $shareWith->getId(),
					'calendarName' => $calendarInfo['uri'],
					'calendarOwner' => $sharedByUid,
				]);
			}
		}
	}
}
