<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Profiler;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\Response;
use OCP\DataCollector\IDataCollector;

/**
 * This interface allows to interact with the built-in Nextcloud profiler.
 * @since 24.0.0
 */
interface IProfiler {
	/**
	 * Add a new data collector to the profiler. This allows to later on
	 * collect all the data from every registered collector.
	 *
	 * @see IDataCollector
	 * @since 24.0.0
	 */
	public function add(IDataCollector $dataCollector): void;

	/**
	 * Load a profile from a response object
	 * @since 24.0.0
	 */
	public function loadProfileFromResponse(Response $response): ?IProfile;

	/**
	 * Load a profile from the response token
	 * @since 24.0.0
	 */
	public function loadProfile(string $token): ?IProfile;

	/**
	 * Save a profile on the disk. This allows to later load it again in the
	 * profiler user interface.
	 * @since 24.0.0
	 */
	public function saveProfile(IProfile $profile): bool;

	/**
	 * Find a profile from various search parameters
	 * @since 24.0.0
	 */
	public function find(?string $url, ?int $limit, ?string $method, ?int $start, ?int $end, ?string $statusCode = null): array;

	/**
	 * Get the list of data providers by identifier
	 * @return string[]
	 * @since 24.0.0
	 */
	public function dataProviders(): array;

	/**
	 * Check if the profiler is enabled.
	 *
	 * If it is not enabled, data provider shouldn't be created and
	 * shouldn't collect any data.
	 * @since 24.0.0
	 */
	public function isEnabled(): bool;

	/**
	 * Set if the profiler is enabled.
	 * @see isEnabled
	 * @since 24.0.0
	 */
	public function setEnabled(bool $enabled): void;

	/**
	 * Collect all the information from the current request and construct
	 * a IProfile from it.
	 * @since 24.0.0
	 */
	public function collect(Request $request, Response $response): IProfile;

	/**
	 * Clear the stored profiles
	 * @since 25.0.0
	 */
	public function clear(): void;
}
