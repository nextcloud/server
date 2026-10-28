<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Test\MessageQueue\Fixtures;

use OCP\MessageQueue\Attribute\AsMessage;

#[AsMessage]
final readonly class NestedMessage {
	public function __construct(
		public string $name,
	) {
	}
}
