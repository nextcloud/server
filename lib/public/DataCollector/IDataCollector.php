<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DataCollector;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\Response;

/**
 * DataCollectorInterface.
 *
 * @since 24.0.0
 */
interface IDataCollector {
	/**
	 * Collects data for the given Request and Response.
	 * @since 24.0.0
	 */
	public function collect(Request $request, Response $response, ?\Throwable $exception = null): void;

	/**
	 * Reset the state of the profiler.
	 * @since 24.0.0
	 */
	public function reset(): void;

	/**
	 * Returns the name of the collector.
	 * @since 24.0.0
	 */
	public function getName(): string;
}
