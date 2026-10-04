<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\MessageQueue;

use OCP\AppFramework\Attribute\Consumable;
use OCP\MessageQueue\Exception\InvalidMessageException;

/**
 * Dispatches messages to be handled asynchronously.
 *
 * Messages are stored in the database. When running under PHP-FPM, messages
 * dispatched during a web request are handled right after the response was
 * sent, except for those in the Low queue. Remaining messages are consumed
 * during the regular cron run. Large instances can run dedicated
 * `occ message-queue:consume` workers.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IMessageBus {
	/**
	 * @param object $message Instance of a class marked with #[AsMessage]
	 * @throws InvalidMessageException if the message is not marked with #[AsMessage],
	 *                                 has no handler or contains unsupported property types
	 * @since 36.0.0
	 */
	public function dispatch(object $message): void;
}
