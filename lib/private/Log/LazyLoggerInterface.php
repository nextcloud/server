<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Log;

use OCP\Log\ILazyLoggerInterface;
use Psr\Log\LoggerInterface;

class LazyLoggerInterface implements ILazyLoggerInterface {
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly int $level,
	) {
	}

	public function print(string $message, array $context = []): void {
		$this->logger->log($this->level, $message, $context);
	}
}
