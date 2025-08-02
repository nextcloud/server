<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\CalDAV\Federation\Protocol\CalendarFederationProtocolV1;
use OCA\DAV\CalDAV\Federation\Protocol\ICalendarFederationProtocol;
use OCA\DAV\DAV\Sharing\Backend as DavSharingBackend;
use OCP\AppFramework\Http;
use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;
use OCP\Federation\ICloudFederationProvider;
use OCP\Federation\ICloudFederationShare;
use Psr\Log\LoggerInterface;

class CalendarFederationProvider implements ICloudFederationProvider {
	public const PROVIDER_ID = 'calendar';
	public const CALENDAR_RESOURCE = 'calendar';
	public const USER_SHARE_TYPE = 'user';

	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly FederatedCalendarMapper $federatedCalendarMapper,
		private readonly CalendarFederationConfig $calendarFederationConfig,
	) {
	}

	public function getShareType(): string {
		return self::PROVIDER_ID;
	}

	public function shareReceived(ICloudFederationShare $share): string {
		if (!$this->calendarFederationConfig->isFederationEnabled()) {
			$this->logger->debug('Received a federation invite but federation is disabled');
			throw new ProviderCouldNotAddShareException(
				'Server does not support talk federation',
				'',
				Http::STATUS_SERVICE_UNAVAILABLE,
			);
		}

		if (!in_array($share->getShareType(), $this->getSupportedShareTypes(), true)) {
			$this->logger->debug('Received a federation invite for invalid share type');
			throw new ProviderCouldNotAddShareException(
				'Support for sharing with non-users not implemented yet',
				'',
				Http::STATUS_NOT_IMPLEMENTED,
			);
			// TODO: Implement group shares
		}

		$rawProtocol = $share->getProtocol();
		// TODO: test what happens if no version in protocol
		switch ($rawProtocol[ICalendarFederationProtocol::PROP_VERSION]) {
			case CalendarFederationProtocolV1::VERSION:
				try {
					$protocol = CalendarFederationProtocolV1::parse($rawProtocol);
				} catch (Protocol\CalendarProtocolParseException $e) {
					throw new ProviderCouldNotAddShareException(
						'Invalid protocol data (v1)',
						'',
						Http::STATUS_BAD_REQUEST,
					);
				}
				$calendarUrl = $protocol->getUrl();
				$displayName = $protocol->getDisplayName();
				$color = $protocol->getColor();
				$access = $protocol->getAccess();
				$components = $protocol->getComponents();
				break;
			default:
				throw new ProviderCouldNotAddShareException(
					'Unknown protocol version',
					'',
					Http::STATUS_BAD_REQUEST,
				);
		}

		if (!$calendarUrl || !$displayName) {
			throw new ProviderCouldNotAddShareException(
				'Incomplete protocol data',
				'',
				Http::STATUS_BAD_REQUEST,
			);
		}

		if ($access !== DavSharingBackend::ACCESS_READ) {
			throw new ProviderCouldNotAddShareException(
				"Unsupported access value: $access",
				'',
				Http::STATUS_BAD_REQUEST,
			);
		}

		// The calendar uri is the local name of the calendar. As such it must not contain slashes.
		// Just use the hashed url for simplicity here.
		// Example: calendars/foo-bar-user/<calendar-uri>
		$calendarUri = hash('md5', $calendarUrl);

		$sharedWithPrincipal = 'principals/users/' . $share->getShareWith();

		// Delete existing incoming federated share first
		$this->federatedCalendarMapper->deleteByUri($sharedWithPrincipal, $calendarUri);

		$calendar = new FederatedCalendarEntity();
		$calendar->setPrincipaluri($sharedWithPrincipal);
		$calendar->setUri($calendarUri);
		$calendar->setRemoteUrl($calendarUrl);
		$calendar->setDisplayName($displayName);
		$calendar->setColor($color);
		$calendar->setToken($share->getShareSecret());
		$calendar->setSharedBy($share->getSharedBy());
		$calendar->setSharedByDisplayName($share->getSharedByDisplayName());
		$calendar->setPermissions($access);
		$calendar->setComponents($components);
		$calendar = $this->federatedCalendarMapper->insert($calendar);
		return (string)$calendar->getId();
	}

	public function notificationReceived($notificationType, $providerId, array $notification) {
		// TODO: implement a notification to queue a sync job immediately if a calendar is changed
	}

	/**
	 * @return string[]
	 */
	public function getSupportedShareTypes(): array {
		return [self::USER_SHARE_TYPE];
	}
}
