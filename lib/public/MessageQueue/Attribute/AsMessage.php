<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\MessageQueue\Attribute;

use OCP\AppFramework\Attribute\Consumable;
use OCP\MessageQueue\Queue;

/**
 * Declares a class as a message that can be dispatched to the message queue.
 *
 * Messages are immutable data objects. They are serialized from their public
 * constructor-promoted properties, which may only be of type int, float,
 * string, bool, null, array (containing only those types), backed enums,
 * \DateTimeImmutable or another class marked with #[AsMessage].
 *
 * Load entities and services inside the handler, never pass them in the message.
 *
 * ```
 * #[AsMessage(queue: Queue::Low, deduplicateBy: ['fileId'], userProperty: 'userId')]
 * final readonly class GeneratePreview {
 *     public function __construct(
 *         public int $fileId,
 *         public string $userId,
 *     ) {
 *     }
 * }
 * ```
 *
 * @since 36.0.0
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
#[Consumable(since: '36.0.0')]
final class AsMessage {
	/**
	 * @param Queue $queue Queue the message is routed to
	 * @param int $maxRetries How often a failed message is retried before it is moved to the failure queue
	 * @param int $retryDelay Delay in seconds before the first retry
	 * @param float $retryMultiplier Factor applied to the delay after each retry
	 * @param list<string> $deduplicateBy Properties forming the deduplication key. While a message
	 *                                    with the same key is pending, dispatching it again is a no-op.
	 *                                    An empty list disables deduplication.
	 * @param string|null $userProperty Property holding a user id. The filesystem and user scope of
	 *                                  that user are set up before the handler runs and torn down after.
	 * @since 36.0.0
	 */
	public function __construct(
		public Queue $queue = Queue::Default,
		public int $maxRetries = 3,
		public int $retryDelay = 60,
		public float $retryMultiplier = 2.0,
		public array $deduplicateBy = [],
		public ?string $userProperty = null,
	) {
	}
}
