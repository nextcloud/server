<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Profiler;

use OCP\DataCollector\IDataCollector;

/**
 * This interface store the results of the profiling of one
 * request. You can get the saved profiles from the @see IProfiler.
 *
 * ```php
 * <?php
 * $profiler = \OCP\Server::get(IProfiler::class);
 * $profiles = $profiler->find('/settings/users', 10);
 * ```
 *
 * This interface is meant to be used directly and not extended.
 * @since 24.0.0
 */
interface IProfile {
	/**
	 * Get the token of the profile
	 * @since 24.0.0
	 */
	public function getToken(): string;

	/**
	 * Set the token of the profile
	 * @since 24.0.0
	 */
	public function setToken(string $token): void;

	/**
	 * Get the time of the profile
	 * @since 24.0.0
	 */
	public function getTime(): ?int;

	/**
	 * Set the time of the profile
	 * @since 24.0.0
	 */
	public function setTime(int $time): void;

	/**
	 * Get the url of the profile
	 * @since 24.0.0
	 */
	public function getUrl(): ?string;

	/**
	 * Set the url of the profile
	 * @since 24.0.0
	 */
	public function setUrl(string $url): void;

	/**
	 * Get the method of the profile
	 * @since 24.0.0
	 */
	public function getMethod(): ?string;

	/**
	 * Set the method of the profile
	 * @since 24.0.0
	 */
	public function setMethod(string $method): void;

	/**
	 * Get the status code of the profile
	 * @since 24.0.0
	 */
	public function getStatusCode(): ?int;

	/**
	 * Set the status code of the profile
	 * @since 24.0.0
	 */
	public function setStatusCode(int $statusCode): void;

	/**
	 * Add a data collector to the profile
	 * @since 24.0.0
	 */
	public function addCollector(IDataCollector $collector);

	/**
	 * Get the parent profile to this profile
	 * @since 24.0.0
	 */
	public function getParent(): ?IProfile;

	/**
	 * Set the parent profile to this profile
	 * @since 24.0.0
	 */
	public function setParent(?IProfile $parent): void;

	/**
	 * Get the parent token to this profile
	 * @since 24.0.0
	 */
	public function getParentToken(): ?string;

	/**
	 * Get the profile's children
	 * @return IProfile[]
	 * @since 24.0.0
	 **/
	public function getChildren(): array;

	/**
	 * Set the profile's children
	 * @param IProfile[] $children
	 * @since 24.0.0
	 */
	public function setChildren(array $children): void;

	/**
	 * Add the child profile
	 * @since 24.0.0
	 */
	public function addChild(IProfile $profile): void;

	/**
	 * Get all the data collectors
	 * @return IDataCollector[]
	 * @since 24.0.0
	 */
	public function getCollectors(): array;

	/**
	 * Set all the data collectors
	 * @param IDataCollector[] $collectors
	 * @since 24.0.0
	 */
	public function setCollectors(array $collectors): void;

	/**
	 * Get a data collector by name
	 * @since 24.0.0
	 */
	public function getCollector(string $collectorName): ?IDataCollector;
}
