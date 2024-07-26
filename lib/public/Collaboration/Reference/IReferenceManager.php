<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	 * @since 30.0.0 optional arguments `$public` and `$sharingToken`
	 */
	public function resolveReference(string $referenceId, bool $public = false, string $sharingToken = ''): ?IReference;

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
	 * @since 30.0.0 optional arguments `$public` and `$sharingToken`
	 */
	public function getReferenceFromCache(string $referenceId, bool $public = false, string $sharingToken = ''): ?IReference;

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
