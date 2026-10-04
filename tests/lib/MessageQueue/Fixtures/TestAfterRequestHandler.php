<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Test\MessageQueue\Fixtures;

use OC\MessageQueue\AfterRequestHandler;

/**
 * Records shutdown functions and the end of the request instead of acting on them.
 */
final class TestAfterRequestHandler extends AfterRequestHandler {
	/** @var list<callable> */
	public array $shutdownFunctions = [];

	public bool $requestFinished = false;

	#[\Override]
	protected function canFinishRequest(): bool {
		return true;
	}

	#[\Override]
	protected function registerShutdownFunction(callable $callback): void {
		$this->shutdownFunctions[] = $callback;
	}

	#[\Override]
	protected function finishRequest(): void {
		$this->requestFinished = true;
	}
}
