<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Test\MessageQueue\Fixtures;

use OCP\MessageQueue\Attribute\AsMessage;

#[AsMessage(userProperty: 'userId')]
final readonly class UserMessage {
	public function __construct(
		public string $userId,
	) {
	}
}
