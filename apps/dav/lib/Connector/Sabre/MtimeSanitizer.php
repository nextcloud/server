<?php
/**
 * @copyright Copyright (c) 2021, Louis Chemineau <louis@chmn.me>
 *
 * @author Louis Chemineau <louis@chmn.me>
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
 *
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
