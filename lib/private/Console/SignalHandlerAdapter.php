<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\Console;

use OCP\Console\ISignalHandler;
use Override;

class SignalHandlerAdapter implements ISignalHandler {
	public function __construct(
		private readonly CommandAdapter $commandAdapter,
	) {
	}

	#[Override]
	public function abortIfInterrupted(): void {
		$this->commandAdapter->abortIfInterrupted();
	}
}
