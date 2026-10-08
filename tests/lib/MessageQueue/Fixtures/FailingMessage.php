<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Test\MessageQueue\Fixtures;

use OCP\MessageQueue\Attribute\AsMessage;

#[AsMessage(maxRetries: 1, retryDelay: 30)]
final readonly class FailingMessage {
	public function __construct(
		public bool $unrecoverable = false,
		public bool $recoverable = false,
	) {
	}
}
