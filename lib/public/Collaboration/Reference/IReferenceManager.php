<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 */

namespace OCP\Collaboration\Reference;

/**
 * @since 25.0.0
 */
interface IReferenceManager {
	/**
	 * Return all reference identifiers within a string as an array
	 *
	 * @return string[] Array of found references (urls)
	 * @since 25.0.0
	 */
	public function extractReferences(string $text): array;

	/**
	 * Resolve a given reference id to its metadata with all available providers
	 *
	 * This method has a fallback to always provide the open graph metadata,
	 * but may still return null in case this is disabled or the fetching fails
	 *
	 * @since 25.0.0
	 */
	public function resolveReference(string $referenceId): ?IReference;

	/**
	 * Get a reference by its cache key
	 *
	 * @since 25.0.0
	 */
	public function getReferenceByCacheKey(string $cacheKey): ?IReference;

	/**
	 * Explicitly get a reference from the cache to avoid heavy fetches for cases
	 * the cache can then be filled with a separate request from the frontend
	 *
	 * @since 25.0.0
	 */
	public function getReferenceFromCache(string $referenceId): ?IReference;

	/**
	 * Invalidate all cache entries with a prefix or just one if the cache key is provided
	 *
	 * @since 25.0.0
	 */
	public function invalidateCache(string $cachePrefix, ?string $cacheKey = null): void;

	/**
	 * Get information on discoverable reference providers (id, title, icon and order)
	 * If the provider is searchable, also get the list of supported unified search providers
	 *
	 * @return IDiscoverableReferenceProvider[]
	 * @since 26.0.0
	 */
	public function getDiscoverableProviders(): array;

	/**
	 * Update or set the last used timestamp for a provider
	 *
	 * @param string $userId
	 * @param string $providerId
	 * @param int|null $timestamp use current timestamp if null
	 * @return bool
	 * @since 26.0.0
	 */
	public function touchProvider(string $userId, string $providerId, ?int $timestamp = null): bool;

	/**
	 * Get all known last used timestamps for reference providers
	 *
	 * @return int[]
	 * @since 26.0.0
	 */
	public function getUserProviderTimestamps(): array;
}
