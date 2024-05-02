<?php

declare(strict_types=1);
/**
 *
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP;

/**
 * @since 28.0.0
 */
interface IPhoneNumberUtil {
	/**
	 * Returns the country code for a specific region
	 *
	 * For example, this would be `41` for Switzerland and `49` for Germany.
	 * Returns null when the region is invalid.
	 *
	 * @param string $regionCode Two-letter region code of ISO 3166-1
	 * @return int|null Null when invalid/unsupported, the phone country code otherwise
	 * @since 28.0.0
	 */
	public function getCountryCodeForRegion(string $regionCode): ?int;

	/**
	 * Converts a given input into an E164 formatted phone number
	 *
	 * E164 is the international format without any formatting characters or spaces.
	 * E.g. +41446681800 where +41 is the region code.
	 *
	 * @param string $input Input phone number can contain formatting spaces, slashes and dashes
	 * @param string|null $defaultRegion Two-letter region code of ISO 3166-1
	 * @return string|null Null when the input is invalid for the given region or requires a region.
	 * @since 28.0.0
	 */
	public function convertToStandardFormat(string $input, ?string $defaultRegion = null): ?string;
}
