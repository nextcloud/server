<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\CalDAV\Federation\Protocol\CalendarFederationProtocolV1;
use OCA\DAV\DAV\RemoteUserPrincipalBackend;
use OCA\DAV\DAV\Sharing\IShareable;
use OCA\DAV\DAV\Sharing\SharingMapper;
use OCP\AppFramework\Http;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;
use Sabre\CalDAV\Calendar;

// TODO: Convert this to an abstract service like the addressbook/calendar sharing services once we
//       support addressbook federation as well.
class FederationSharingService {
	public function __construct(
		private readonly ICloudFederationProviderManager $federationManager,
		private readonly ICloudFederationFactory $federationFactory,
		private readonly IUserManager $userManager,
		private readonly IURLGenerator $url,
		private readonly LoggerInterface $logger,
		private readonly ISecureRandom $random,
		private readonly SharingMapper $sharingMapper,
	) {
	}

	/**
	 * Decode a (base64) encoded remote user principal and return the remote user's cloud id. Will
	 * return null if the given principal is not belonging to a remote user (or has an invalid
	 * format).
	 *
	 * The remote user/cloud id needs to be encoded as it might contain slashes.
	 */
	private function decodeRemoteUserPrincipal(string $principal): ?string {
		// Expected format: principals/remote-users/abcdef123
		[$prefix, $collection, $encodedId] = explode('/', $principal);
		if ($prefix !== 'principals' || $collection !== 'remote-users') {
			return null;
		}

		$decodedId = base64_decode($encodedId);
		if (!is_string($decodedId)) {
			return null;
		}

		return $decodedId;
	}

	/**
	 * Send a calendar share to a remote instance and create a federated share locally if it is
	 * accepted.
	 *
	 * @param IShareable $shareable The calendar to be shared.
	 * @param string $principal The principal to share with (should be a remote user principal).
	 * @param int $access The access level. The remote serve might reject it.
	 */
	public function shareWith(IShareable $shareable, string $principal, int $access): void {
		$baseError = 'Failed to create federated calendar share: ';

		// 1. Validate share data
		$shareWith = $this->decodeRemoteUserPrincipal($principal);
		if ($shareWith === null) {
			$this->logger->error($baseError . 'Principal of sharee is not belonging to a remote user', [
				'shareable' => $shareable->getName(),
				'encodedShareWith' => $principal,
			]);
			return;
		}

		[,, $ownerUid] = explode('/', $shareable->getOwner());
		$owner = $this->userManager->get($ownerUid);
		if ($owner === null) {
			$this->logger->error($baseError . 'Shareable is not owned by a user on this server', [
				'shareable' => $shareable->getName(),
				'shareWith' => $shareWith,
			]);
			return;
		}

		// Need a calendar instance to extract properties for the protocol
		$calendar = $shareable;
		if (!($calendar instanceof Calendar)) {
			$this->logger->error($baseError . 'Shareable is not a calendar', [
				'shareable' => $shareable->getName(),
				'owner' => $owner,
				'shareWith' => $shareWith,
			]);
			return;
		}

		$getProp = static fn (string $prop) => $calendar->getProperties([$prop])[$prop] ?? null;

		$displayName = $getProp('{DAV:}displayname') ?? '';

		$token = $this->random->generate(32);
		$share = $this->federationFactory->getCloudFederationShare(
			$shareWith,
			$shareable->getName(),
			$displayName,
			CalendarFederationProvider::PROVIDER_ID,
			// Resharing is not possible so the owner is always the sharer
			$owner->getCloudId(),
			$owner->getDisplayName(),
			$owner->getCloudId(),
			$owner->getDisplayName(),
			$token,
			CalendarFederationProvider::USER_SHARE_TYPE,
			CalendarFederationProvider::CALENDAR_RESOURCE,
		);

		// 2. Send share to federated instance
		$shareWithEncoded = base64_encode($shareWith);
		$relativeCalendarUrl = "remote-calendars/$shareWithEncoded/" . $calendar->getName() . '_shared_by_' . $ownerUid;
		$calendarUrl = $this->url->linkTo('', 'remote.php') . "/dav/$relativeCalendarUrl";
		$calendarUrl = $this->url->getAbsoluteURL($calendarUrl);
		$protocol = new CalendarFederationProtocolV1();
		$protocol->setUrl($calendarUrl);
		$protocol->setDisplayName($displayName);
		$protocol->setColor($getProp('{http://apple.com/ns/ical/}calendar-color'));
		$protocol->setAccess($access);
		$protocol->setComponents(implode(',', $getProp(
			'{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set')?->getValue() ?? [],
		));
		$share->setProtocol([
			// Preserve original protocol contents
			...$share->getProtocol(),
			...$protocol->toProtocol(),
		]);

		try {
			$response = $this->federationManager->sendCloudShare($share);
		} catch (OCMProviderException $e) {
			$this->logger->error($baseError . $e->getMessage(), [
				'exception' => $e,
				'owner' => $owner->getUID(),
				'calendar' => $shareable->getName(),
				'shareWith' => $shareWith,
			]);
			return;
		}

		if ($response->getStatusCode() !== Http::STATUS_CREATED) {
			$this->logger->error($baseError . 'Server replied with code ' . $response->getStatusCode(), [
				'responseBody' => $response->getBody(),
				'owner' => $owner->getUID(),
				'calendar' => $shareable->getName(),
				'shareWith' => $shareWith,
			]);
			return;
		}

		// 3. Create a local DAV share to track the token for authentication
		$shareWithPrincipalUri = RemoteUserPrincipalBackend::PRINCIPAL_PREFIX . '/' . $shareWithEncoded;
		$this->sharingMapper->deleteShare(
			$shareable->getResourceId(),
			'calendar',
			$shareWithPrincipalUri,
		);
		$this->sharingMapper->shareWithToken(
			$shareable->getResourceId(),
			'calendar',
			$access,
			$shareWithPrincipalUri,
			$token,
		);
	}
}
