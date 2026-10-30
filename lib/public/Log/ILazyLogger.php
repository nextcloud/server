<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Log;

/**
 * will only initialize the payload if needed:
 *    $this->logger->debug?->print('my message', ['data' => my_heavy_process()]);
 *
 * if loglevel does not allow it, the payload will not be loaded/initialized
 *
 * @since 39.0.0
 */
interface ILazyLogger {
	public ?ILazyLoggerInterface $debug { get; }
	public ?ILazyLoggerInterface $info { get; }
	public ?ILazyLoggerInterface $warning { get; }
	public ?ILazyLoggerInterface $error { get; }
	public ?ILazyLoggerInterface $fatal { get; }
}
