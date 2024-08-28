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
interface IReferenceProvider {
	/**
	 * Validate that a given reference identifier matches the current provider
	 *
	 * @since 25.0.0
	 */
	public function matchReference(string $referenceText): bool;

	/**
	 * Return a reference with its metadata for a given reference identifier
	 *
	 * @since 25.0.0
	 */
	public function resolveReference(string $referenceText): ?IReference;

	/**
	 * Return true if the reference metadata can be globally cached
	 *
	 * @since 25.0.0
	 */
	public function getCachePrefix(string $referenceId): string;

	/**
	 * Return a custom cache key to be used for caching the metadata
	 * This could be for example the current user id if the reference
	 * access permissions are different for each user
	 *
	 * Should return null, if the cache is only related to the
	 * reference id and has no further dependency
	 *
	 * @since 25.0.0
	 */
	public function getCacheKey(string $referenceId): ?string;
}
