<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\Location;

/**
 * This interface must be used when creating a Location Provider
 *
 * ## Shared options
 *
 *  `"lang"` Lang in which to prefer results. Used as a request parameter or
 * through an `Accept-Language` HTTP header. Defaults to `"en"`.
 *  `"country_code"` An ISO 3166 country code. String or `nil`
 *  `"limit"` Maximum limit for the number of results returned by the backend.
 * Defaults to `10`
 *  `"api_key"` Allows to override the API key (if the backend requires one) set
 * inside the configuration.
 *  `"endpoint"` Allows to override the endpoint set inside the configuration.
 *
 * @since 28.0.0
 */
interface ILocationProvider {
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
	 * @since 28.0.0
	 */
	public function search(string $address, array $options = []): array;
}
