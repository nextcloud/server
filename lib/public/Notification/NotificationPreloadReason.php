<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Notification;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Indicates the reason for preloading notifications to facilitate smarter decisions about what data
 * to preload.
 */
#[Consumable(since: '32.0.0')]
enum NotificationPreloadReason {
	/**
	 * Preparing a single notification for many users.
	 *
	 * @since 32.0.0
	 */
	case Push;

	/**
	 * Preparing many notifications for many users.
	 *
	 * @since 32.0.0
	 */
	case Email;

	/**
	 * Preparing many notifications for a single user.
	 *
	 * @since 32.0.0
	 */
	case EndpointController;
}
