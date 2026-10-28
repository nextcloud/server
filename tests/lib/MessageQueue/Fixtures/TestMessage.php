<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Test\MessageQueue\Fixtures;

use OCP\MessageQueue\Attribute\AsMessage;
use OCP\MessageQueue\Queue;

#[AsMessage(queue: Queue::High, maxRetries: 2, retryDelay: 10, retryMultiplier: 3.0, deduplicateBy: ['id'])]
final readonly class TestMessage {
	public function __construct(
		public int $id,
		public string $text = 'default',
		public ?Status $status = null,
		public ?\DateTimeImmutable $date = null,
		public ?NestedMessage $nested = null,
		public array $list = [],
		public float $ratio = 1.0,
	) {
	}
}
