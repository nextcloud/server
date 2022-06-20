<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\Security;

use OCP\Security\ISecureRandom;

/**
 * Class SecureRandom provides a wrapper around the random_int function to generate
 * secure random strings. For PHP 7 the native CSPRNG is used, older versions do
 * use a fallback.
 *
 * Usage:
 * \OC::$server->getSecureRandom()->generate(10);
 * @package OC\Security
 */
class SecureRandom implements ISecureRandom {
	/**
	 * Generate a secure random string of specified length.
	 * @param int $length The length of the generated string
	 * @param string $characters An optional list of characters to use if no character list is
	 * 							specified all valid base64 characters are used.
	 * @return string
	 * @throws \LengthException if an invalid length is requested
	 */
	public function generate(int $length,
							 string $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'): string {
		if ($length <= 0) {
			throw new \LengthException('Invalid length specified: ' . $length . ' must be bigger than 0');
		}

		$maxCharIndex = \strlen($characters) - 1;
		$randomString = '';

		while ($length > 0) {
			$randomNumber = \random_int(0, $maxCharIndex);
			$randomString .= $characters[$randomNumber];
			$length--;
		}
		return $randomString;
	}
}
