<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Test\MessageQueue\Fixtures;

use OCP\MessageQueue\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class InvokableHandler {
	/** @var list<TestMessage> */
	public array $handled = [];

	public function __invoke(TestMessage $message): void {
		$this->handled[] = $message;
	}
}
