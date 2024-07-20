<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Connector\Sabre;

class MtimeSanitizer {
	public static function sanitizeMtime(string $mtimeFromRequest): int {
		// In PHP 5.X "is_numeric" returns true for strings in hexadecimal
		// notation. This is no longer the case in PHP 7.X, so this check
		// ensures that strings with hexadecimal notations fail too in PHP 5.X.
		$isHexadecimal = preg_match('/^\s*0[xX]/', $mtimeFromRequest);
		if ($isHexadecimal || !is_numeric($mtimeFromRequest)) {
			throw new \InvalidArgumentException('X-OC-MTime header must be an integer (unix timestamp).');
		}

		// Prevent writing invalid mtime (timezone-proof)
		if ((int)$mtimeFromRequest <= 24 * 60 * 60) {
			throw new \InvalidArgumentException('X-OC-MTime header must be a valid positive integer');
		}

		return (int)$mtimeFromRequest;
	}
}
