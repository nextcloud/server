<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Log;

use OC;
use OCP\AppFramework\QueryException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function class_exists;

/**
 * Get a PSR logger
 *
 * Whenever possible, inject a logger into your classes instead of relying on
 * this helper function.
 *
 * @warning the returned logger implementation is not guaranteed to be the same
 *          between two function calls. During early stages of the process you
 *          might in fact get a noop implementation when Nextcloud isn't ready
 *          to log. Therefore you MUST NOT cache the result of this function but
 *          fetch a new logger for every log line you want to write.
 *
 * @param string|null $appId optional parameter to acquire the app-specific logger
 *
 * @return LoggerInterface
 * @since 24.0.0
 */
function logger(?string $appId = null): LoggerInterface {
	/** @psalm-suppress TypeDoesNotContainNull false-positive, it may contain null if we are logging from initialization */
	if (!class_exists(OC::class) || OC::$server === null) {
		// If someone calls this log before Nextcloud is initialized, there is
		// no logging available. In that case we return a noop implementation
		// TODO: evaluate whether logging to error_log could be an alternative
		return new NullLogger();
	}

	if ($appId !== null) {
		try {
			$appContainer = OC::$server->getRegisteredAppContainer($appId);
			return $appContainer->get(LoggerInterface::class);
		} catch (QueryException $e) {
			// Ignore and return the server logger below
		}
	}

	try {
		return OC::$server->get(LoggerInterface::class);
	} catch (QueryException $e) {
		return new NullLogger();
	}
}
