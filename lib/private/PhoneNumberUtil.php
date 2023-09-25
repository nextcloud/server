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
