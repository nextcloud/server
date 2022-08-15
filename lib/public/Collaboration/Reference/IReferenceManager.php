<?php
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
	 * @since 25.0.0
	 */
	public function extractReferences(string $text): array;

	/**
	 * Resolve a given reference id to its metadata with all available providers
	 *
	 * This method has a fallback to always provide the open graph metadata,
	 * but may still return null in case this is disabled or the fetching fails
	 * @since 25.0.0
	 */
	public function resolveReference(string $reference): ?IReference;

	/**
	 * Register a new reference provider
	 *
	 * @since 25.0.0
	 */
	public function registerReferenceProvider(IReferenceProvider $provider): void;
}
