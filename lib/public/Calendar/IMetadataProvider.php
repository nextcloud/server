<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

/**
 * Interface IMetadataProvider
 *
 * Provider for metadata of a resource or a room
 *
 * @since 17.0.0
 */
interface IMetadataProvider {
	/**
	 * Get a list of all metadata keys available for this room
	 *
	 * Room backends are allowed to return custom keys, beyond the ones
	 * defined in this class. If they do, they should make sure to use their
	 * own namespace.
	 *
	 * @return String[] - A list of available keys
	 * @since 17.0.0
	 */
	public function getAllAvailableMetadataKeys():array;

	/**
	 * Get whether or not a metadata key is set for this room
	 *
	 * @param string $key - The key to check for
	 * @return bool - Whether or not key is available
	 * @since 17.0.0
	 */
	public function hasMetadataForKey(string $key):bool;

	/**
	 * Get the value for a metadata key
	 *
	 * @param string $key - The key to check for
	 * @return string|null - The value stored for the key, null if no value stored
	 * @since 17.0.0
	 */
	public function getMetadataForKey(string $key):?string;
}
