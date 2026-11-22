<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudId;
use OCP\Http\Client\IResponse;
use OCP\IURLGenerator;
use OCP\OCM\Exceptions\OCMProviderException;

class CalendarFederationNotifier {
	public const NOTIFICATION_SYNC_CALENDAR = 'SYNC_CALENDAR';

	public const PROP_SYNC_CALENDAR_SHARE_WITH = 'shareWith';
	public const PROP_SYNC_CALENDAR_CALENDAR_URL = 'calendarUrl';

	public function __construct(
		private readonly ICloudFederationFactory $federationFactory,
		private readonly ICloudFederationProviderManager $federationManager,
		private readonly IURLGenerator $url,
	) {
	}

	/**
	 * Notify a remote server to sync a calendar.
	 *
	 * @param ICloudId $shareWith The cloud id of the remote sharee.
	 * @return IResponse
	 *
	 * @throws OCMProviderException If sending the notification fails.
	 */
	public function notifySyncCalendar(
		ICloudId $shareWith,
		string $calendarOwner,
		string $calendarName,
		string $sharedSecret,
	): IResponse {
		$sharedWithEncoded = base64_encode($shareWith->getId());
		$relativeCalendarUrl = "remote-calendars/$sharedWithEncoded/{$calendarName}_shared_by_$calendarOwner";
		$calendarUrl = $this->url->linkTo('', 'remote.php') . "/dav/$relativeCalendarUrl";
		$calendarUrl = $this->url->getAbsoluteURL($calendarUrl);

		$notification = $this->federationFactory->getCloudFederationNotification();
		$notification->setMessage(
			self::NOTIFICATION_SYNC_CALENDAR,
			CalendarFederationProvider::CALENDAR_RESOURCE,
			CalendarFederationProvider::PROVIDER_ID,
			[
				'sharedSecret' => $sharedSecret,
				self::PROP_SYNC_CALENDAR_SHARE_WITH => $shareWith->getId(),
				self::PROP_SYNC_CALENDAR_CALENDAR_URL => $calendarUrl,
			],
		);

		return $this->federationManager->sendCloudNotification(
			$shareWith->getRemote(),
			$notification,
		);
	}
}
