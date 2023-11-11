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
 * This interface must be used when creating a Location Autocomplete Provider
 *
 * @since 28.0.0
 */
interface ILocationAutocompleteProvider extends ILocationProvider {
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
	 * @since 28.0.0
	 */
	public function autocomplete(string $address, array $options = []): array;
}
