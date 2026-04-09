<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use OCP\IPhoneNumberUtil;

/**
 * @since 28.0.0
 */
class PhoneNumberUtil implements IPhoneNumberUtil {
	/**
	 * {@inheritDoc}
	 */
	public function getCountryCodeForRegion(string $regionCode): ?int {
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		$countryCode = $phoneUtil->getCountryCodeForRegion($regionCode);
		return $countryCode === 0 ? null : $countryCode;
	}

	/**
	 * {@inheritDoc}
	 */
	public function convertToStandardFormat(string $input, ?string $defaultRegion = null): ?string {
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {
			$phoneNumber = $phoneUtil->parse($input, $defaultRegion);
			if ($phoneUtil->isValidNumber($phoneNumber)) {
				return $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
			}
		} catch (NumberParseException) {
		}

		return null;
	}
}
