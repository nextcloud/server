<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
