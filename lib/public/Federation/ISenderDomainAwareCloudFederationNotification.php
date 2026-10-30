<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Federation;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Notification received on the OCM notifications endpoint.
 *
 * @link https://github.com/cs3org/OCM-API/blob/v1.5.0/IETF-OCM.md#notifications
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface ISenderDomainAwareCloudFederationNotification extends ICloudFederationNotification {
	/**
	 * Notification type, e.g. SHARE_ACCEPTED
	 *
	 * @since 36.0.0
	 */
	public function getNotificationType(): string;

	/**
	 * Domain of the sending server, verified against the request signature.
	 * Null for notifications without a senderDomain field.
	 *
	 * @since 36.0.0
	 */
	public function getSenderDomain(): ?string;

	/**
	 * Type of the resource the notification is about, e.g. file
	 *
	 * @since 36.0.0
	 */
	public function getResourceType(): ?string;

	/**
	 * Type of the recipient the notification is about, e.g. user
	 *
	 * @since 36.0.0
	 */
	public function getShareType(): ?string;

	/**
	 * Identifier assigned by the sending server to the underlying share
	 *
	 * @since 36.0.0
	 * @deprecated 36.0.0 deprecated by the OCM specification, identify the share from {@see getNotification()} instead
	 */
	public function getProviderId(): ?string;

	/**
	 * Additional parameters, depending on the notification type and the resource type
	 *
	 * @return array<string, mixed>
	 * @since 36.0.0
	 */
	public function getNotification(): array;
}
