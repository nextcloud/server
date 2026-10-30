<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OCM\Events;

use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;
use OCP\Federation\ICloudFederationNotification;
use OCP\Federation\ISenderDomainAwareCloudFederationNotification;

/**
 * @since 35.0.0
 */
#[Listenable(since: '35.0.0')]
class OCMNotificationReceivedEvent extends Event {
	/** @var array<string, mixed>|null */
	private ?array $result = null;

	/**
	 * @since 35.0.0
	 */
	public function __construct(
		private readonly ICloudFederationNotification $notification,
	) {
		parent::__construct();
	}

	/**
	 * Notifications received on the OCM notifications endpoint are
	 * {@see ISenderDomainAwareCloudFederationNotification}
	 *
	 * @since 35.0.0
	 */
	public function getNotification(): ICloudFederationNotification {
		return $this->notification;
	}

	/**
	 * Set the data sent back to the sender. Only used for notifications with a
	 * senderDomain field, which are handled by listeners instead of a cloud
	 * federation provider.
	 *
	 * @param array<string, mixed> $result
	 * @since 36.0.0
	 */
	public function setResult(array $result): void {
		$this->result = $result;
	}

	/**
	 * @return array<string, mixed>|null null if no listener handled the notification
	 * @since 36.0.0
	 */
	public function getResult(): ?array {
		return $this->result;
	}
}
