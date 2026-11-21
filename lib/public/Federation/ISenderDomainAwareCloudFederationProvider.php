<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Federation;

use OCP\AppFramework\Attribute\Implementable;
use OCP\Federation\Exceptions\ActionNotSupportedException;
use OCP\Federation\Exceptions\AuthenticationFailedException;
use OCP\Federation\Exceptions\BadRequestException;
use OCP\Share\Exceptions\ShareNotFound;

/**
 * Cloud federation provider that handles notifications in the OCM 1.5+ format
 *
 * @link https://github.com/cs3org/OCM-API/blob/v1.5.0/IETF-OCM.md#notifications
 * @since 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface ISenderDomainAwareCloudFederationProvider extends ICloudFederationProvider {
	/**
	 * Notification with senderDomain field received from another server
	 *
	 * @param string $notificationType (e.g SHARE_ACCEPTED)
	 * @param string|null $providerId share ID, deprecated by the OCM spec and may be omitted by the sender
	 * @param array<string, mixed> $notification provider specific notification
	 * @param string $senderDomain domain of the sending server, verified against the request signature.
	 *                             Providers must still verify that the sender is allowed to act on the resource.
	 * @param string|null $shareType type of the recipient the notification is about, e.g. user
	 * @return array<string, mixed> data sent back to the sender
	 *
	 * @throws ShareNotFound
	 * @throws ActionNotSupportedException
	 * @throws BadRequestException
	 * @throws AuthenticationFailedException
	 *
	 * @since 36.0.0
	 */
	public function senderDomainAwareNotificationReceived(string $notificationType, ?string $providerId, array $notification, string $senderDomain, ?string $shareType): array;
}
