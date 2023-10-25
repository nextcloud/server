<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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


namespace OCP\Location;

use OCP\PreConditionNotMetException;

/**
 * @since 28.0.0
 */
interface ILocationManager {
	/**
	 * @since 28.0.0
	 */
	public function hasProviders(): bool;

	/**
	 * @return ILocationProvider[]
	 * @since 28.0.0
	 */
	public function getProviders(): array;

	/**
	 * Gets an address from longitude and latitude coordinates.
	 *
	 * ## Options
	 *
	 * In addition to [the shared options](#module-shared-options), the function also accepts the following options:
	 *
	 * - `zoom` Level of detail required for the address. Default: 15
	 *
	 * @return ILocationAddress[]
	 * @throws CouldNotGeocodeException
	 * @throws PreConditionNotMetException
	 * @since 28.0.0
	 */
	public function geocode(float $longitude, float $latitude, array $options = []): array;

	/**
	 * Search for an address
	 *
	 * ## Options
	 *
	 * In addition to [the shared options](#module-shared-options), the function also accepts the following options:
	 *
	 *  `"coords"` Map of coordinates (ex: `["lon" => 48.11, "lat" => -1.77]`) allowing to
	 * give a geographic priority to the search. Defaults to `null`.
	 *  `"type"` Filter by type of results. Allowed values:
	 *  `"administrative"` (cities, regions)
	 *
	 * @return ILocationAddress[]
	 * @throws CouldNotSearchLocationException
	 * @throws PreConditionNotMetException
	 * @since 28.0.0
	 */
	public function search(string $address, array $options = []): array;

	/**
	 * Autocomplete an address
	 *
	 * ## Options
	 *
	 * In addition to [the shared options](#module-shared-options), the function also accepts the following options:
	 *
	 *  `"coords"` Map of coordinates (ex: `["lon" => 48.11, "lat" => -1.77]`) allowing to
	 * give a geographic priority to the search. Defaults to `null`.
	 *  `"type"` Filter by type of results. Allowed values:
	 *  `"administrative"` (cities, regions)
	 *
	 * @return ILocationAddress[]
	 * @throws CouldNotSearchLocationException
	 * @throws PreConditionNotMetException
	 * @since 28.0.0
	 */
	public function autocomplete(string $address, array $options = []): array;


	/**
	 * Returns whether a location provider capable of auto-complete has been registered
	 * @since 28.0.0
	 */
	public function canAutocomplete(): bool;
}
