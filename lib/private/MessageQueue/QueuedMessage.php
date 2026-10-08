<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\MessageQueue;

/**
 * A message claimed from the store, not yet decoded.
 */
final readonly class QueuedMessage {
	public function __construct(
		public string $id,
		public string $messageClass,
		public string $body,
		public int $retryCount,
		/** Previous delivery time, used to claim the message atomically */
		public ?int $deliveredAt,
	) {
	}
}
