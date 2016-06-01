<?php
/**
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * Class SecureRandom provides a wrapper around the random_int function to generate
 * secure random strings. For PHP 7 the native CSPRNG is used, older versions do
 * use a fallback.
 *
 * Usage:
 * \OC::$server->getSecureRandom()->generate(10);
 *
 * @package OCP\Security
 * @since 8.0.0
 */
interface ISecureRandom {

	/**
	 * Flags for characters that can be used for <code>generate($length, $characters)</code>
	 */
	const CHAR_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const CHAR_LOWER = 'abcdefghijklmnopqrstuvwxyz';
	const CHAR_DIGITS = '0123456789';
	const CHAR_SYMBOLS = '!\"#$%&\\\'()* +,-./:;<=>?@[\]^_`{|}~';

	/**
	 * Convenience method to get a low strength random number generator.
	 *
	 * Low Strength should be used anywhere that random strings are needed
	 * in a non-cryptographical setting. They are not strong enough to be
	 * used as keys or salts. They are however useful for one-time use tokens.
	 *
	 * @return $this
	 * @since 8.0.0
	 * @deprecated 9.0.0 Use \OC\Security\SecureRandom::generate directly or random_bytes() / random_int()
	 */
	public function getLowStrengthGenerator();

	/**
	 * Convenience method to get a medium strength random number generator.
	 *
	 * Medium Strength should be used for most needs of a cryptographic nature.
	 * They are strong enough to be used as keys and salts. However, they do
	 * take some time and resources to generate, so they should not be over-used
	 *
	 * @return $this
	 * @since 8.0.0
	 * @deprecated 9.0.0 Use \OC\Security\SecureRandom::generate directly or random_bytes() / random_int()
	 */
	public function getMediumStrengthGenerator();

	/**
	 * Generate a random string of specified length.
	 * @param int $length The length of the generated string
	 * @param string $characters An optional list of characters to use if no character list is
	 * 							specified all valid base64 characters are used.
	 * @return string
	 * @since 8.0.0
	 */
	public function generate($length,
							 $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/');

}
