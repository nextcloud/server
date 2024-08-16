<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Service;

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;

class BirthdateParserService {
	/**
	 * Try to parse the birthdate from LDAP.
	 * Supports LDAP's generalized time syntax, YYYYMMDD and YYYY-MM-DD.
	 *
	 * @throws InvalidArgumentException If the format of then given date is unknown
	 */
	public function parseBirthdate(string $value): DateTimeImmutable {
		// Minimum LDAP generalized date is "1994121610Z" with 11 chars
		// While maximum other format is "1994-12-16" with 10 chars
		if (strlen($value) > strlen('YYYY-MM-DD')) {
			// Probably LDAP generalized time syntax
			$value = substr($value, 0, 8);
		}

		// Should be either YYYYMMDD or YYYY-MM-DD
		if (!preg_match('/^(\d{8}|\d{4}-\d{2}-\d{2})$/', $value)) {
			throw new InvalidArgumentException("Unknown date format: $value");
		}

		try {
			return new DateTimeImmutable($value);
		} catch (Exception $e) {
			throw new InvalidArgumentException(
				"Unknown date format: $value",
				0,
				$e,
			);
		}
	}
}
