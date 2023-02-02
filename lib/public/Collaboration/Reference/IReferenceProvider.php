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
