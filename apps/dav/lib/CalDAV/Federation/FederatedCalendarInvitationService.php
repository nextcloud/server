<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\BackgroundJob\FederatedCalendarSyncJob;
use OCA\Federation\TrustedServers;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\Federation\ICloudIdManager;
use OCP\IURLGenerator;
use OCP\Notification\IManager as INotificationManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class FederatedCalendarInvitationService {
	public const NOTIFICATION_OBJECT_TYPE = 'federated_calendar_share';
	public const NOTIFICATION_SUBJECT_NEW_SHARE = 'federated_calendar_share';

	private const PRINCIPAL_PREFIX = 'principals/users/';

	public function __construct(
		private readonly FederatedCalendarMapper $mapper,
		private readonly IJobList $jobList,
		private readonly INotificationManager $notificationManager,
		private readonly IURLGenerator $url,
		private readonly ICloudIdManager $cloudIdManager,
		private readonly CalendarFederationConfig $config,
		private readonly IAppManager $appManager,
		private readonly LoggerInterface $logger,
	) {
	}

	public function accept(FederatedCalendarEntity $calendar): void {
		$calendar->setState(FederatedCalendarEntity::STATE_ACCEPTED);
		$this->mapper->update($calendar);

		$this->jobList->add(FederatedCalendarSyncJob::class, [
			FederatedCalendarSyncJob::ARGUMENT_ID => $calendar->getId(),
		]);

		$this->dismissNotification($calendar);
	}

	public function decline(FederatedCalendarEntity $calendar): void {
		// Dismiss first: a leftover bell notification would offer actions for
		// a share that no longer exists
		$this->dismissNotification($calendar);
		$this->mapper->deleteById($calendar->getId());
	}

	public function notifyAboutNewShare(FederatedCalendarEntity $calendar): void {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp(Application::APP_ID)
			->setUser($this->getUserId($calendar))
			->setDateTime(new \DateTime())
			->setObject(self::NOTIFICATION_OBJECT_TYPE, (string)$calendar->getId())
			->setSubject(self::NOTIFICATION_SUBJECT_NEW_SHARE, [
				'sharedBy' => $calendar->getSharedBy(),
				'sharedByDisplayName' => $calendar->getSharedByDisplayName(),
				'calendarName' => $calendar->getDisplayName(),
			]);

		$endpointUrl = $this->url->linkToOCSRouteAbsolute('dav.federated_calendar.accept', [
			'id' => $calendar->getId(),
		]);

		$declineAction = $notification->createAction();
		$declineAction->setLabel('decline')
			->setLink($endpointUrl, 'DELETE');
		$notification->addAction($declineAction);

		$acceptAction = $notification->createAction();
		$acceptAction->setLabel('accept')
			->setLink($endpointUrl, 'POST');
		$notification->addAction($acceptAction);

		$this->notificationManager->notify($notification);
	}

	public function dismissNotification(FederatedCalendarEntity $calendar): void {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp(Application::APP_ID)
			->setUser($this->getUserId($calendar))
			->setObject(self::NOTIFICATION_OBJECT_TYPE, (string)$calendar->getId());
		$this->notificationManager->markProcessed($notification);
	}

	public function shouldAutoAccept(?string $ownerCloudId, string $remoteUrl): bool {
		if ($ownerCloudId === null || $ownerCloudId === '') {
			return false;
		}

		if (!$this->config->isTrustedShareAutoAcceptEnabled()) {
			return false;
		}

		$trustedServers = $this->getTrustedServers();
		if ($trustedServers === null) {
			return false;
		}

		try {
			$remote = $this->cloudIdManager->resolveCloudId($ownerCloudId)->getRemote();
		} catch (\InvalidArgumentException) {
			return false;
		}

		if (!$trustedServers->isTrustedServer($remote)) {
			return false;
		}

		return $this->isSameHost($remote, $remoteUrl);
	}

	private function isSameHost(string $remote, string $remoteUrl): bool {
		$remoteParts = parse_url(str_contains($remote, '://') ? $remote : '//' . $remote);
		$urlParts = parse_url($remoteUrl);
		if (!is_array($remoteParts) || !is_array($urlParts)
			|| !isset($remoteParts['host'], $urlParts['host'])) {
			return false;
		}

		return strcasecmp($remoteParts['host'], $urlParts['host']) === 0
			&& ($remoteParts['port'] ?? null) === ($urlParts['port'] ?? null);
	}

	protected function getTrustedServers(): ?TrustedServers {
		if (!$this->appManager->isEnabledForAnyone('federation')
			|| !class_exists(TrustedServers::class)) {
			return null;
		}

		try {
			return Server::get(TrustedServers::class);
		} catch (\Throwable $e) {
			$this->logger->debug('Failed to create TrustedServers', ['exception' => $e]);
			return null;
		}
	}

	private function getUserId(FederatedCalendarEntity $calendar): string {
		return substr($calendar->getPrincipaluri(), strlen(self::PRINCIPAL_PREFIX));
	}
}
