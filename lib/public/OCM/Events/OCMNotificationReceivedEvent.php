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

/**
 * @since 35.0.0
 */
#[Listenable(since: '35.0.0')]
class OCMNotificationReceivedEvent extends Event {
	/**
	 * @since 35.0.0
	 */
	public function __construct(
		private readonly ICloudFederationNotification $notification,
	) {
		parent::__construct();
	}

	/**
	 * @since 35.0.0
	 */
	public function getNotification(): ICloudFederationNotification {
		return $this->notification;
	}
}
