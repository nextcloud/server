<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\MessageQueue;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Cron consumes queues in order of priority: High before Default before Low.
 * Workers consume the queues passed to `occ message-queue:consume` in the given order.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum Queue: string {
	/**
	 * Interactive work a user is waiting for.
	 * @since 36.0.0
	 */
	case High = 'high';
	/**
	 * @since 36.0.0
	 */
	case Default = 'default';
	/**
	 * Expensive work. When a maintenance window is configured, system cron
	 * only consumes it during that window. AJAX and webcron always consume it.
	 * @since 36.0.0
	 */
	case Low = 'low';
}
