<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Test\MessageQueue\Fixtures;

use OCP\MessageQueue\Attribute\AsMessageHandler;

final class DuplicateHandler {
	#[AsMessageHandler]
	public function duplicate(TestMessage $message): void {
	}

	#[AsMessageHandler]
	public function invalid(NotAMessage $message): void {
	}
}
