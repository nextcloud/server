<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Security;

class StringUtils {

	/**
	 * Compares whether two strings are equal. To prevent guessing of the string
	 * length this is done by comparing two hashes against each other and afterwards
	 * a comparison of the real string to prevent against the unlikely chance of
	 * collisions.
	 *
	 * Be aware that this function may leak whether the string to compare have a different
	 * length.
	 *
	 * @param string $expected The expected value
	 * @param string $input The input to compare against
	 * @return bool True if the two strings are equal, otherwise false.
	 */
	public static function equals($expected, $input) {

		if(!is_string($expected) || !is_string($input)) {
			return false;
		}

		if(function_exists('hash_equals')) {
			return hash_equals($expected, $input);
		}

		$randomString = \OC::$server->getSecureRandom()->getLowStrengthGenerator()->generate(10);

		if(hash('sha512', $expected.$randomString) === hash('sha512', $input.$randomString)) {
			if($expected === $input) {
				return true;
			}
		}

		return false;
	}
}