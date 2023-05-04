<?php

declare(strict_types=1);

/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Profiler;

use OCP\DataCollector\IDataCollector;

/**
 * This interface store the results of the profiling of one
 * request. You can get the saved profiles from the @see IProfiler.
 *
 * ```php
 * <?php
 * $profiler = \OC::$server->get(IProfiler::class);
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
