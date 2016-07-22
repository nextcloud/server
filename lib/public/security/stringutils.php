<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OCP\Security;

/**
 * Class StringUtils
 *
 * @package OCP\Security
 * @since 8.0.0
 */
class StringUtils {
	/**
	 * Compares whether two strings are equal. To prevent guessing of the string
	 * length this is done by comparing two hashes against each other and afterwards
	 * a comparison of the real string to prevent against the unlikely chance of
	 * collisions.
	 * @param string $expected The expected value
	 * @param string $input The input to compare against
	 * @return bool True if the two strings are equal, otherwise false.
	 * @since 8.0.0
	 * @deprecated 9.0.0 Use hash_equals
	 */
	public static function equals($expected, $input) {
		return hash_equals($expected, $input);
	}
}
