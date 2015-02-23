<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Security;

/**
 * Class SecureRandom provides a layer around RandomLib to generate
 * secure random numbers.
 *
 * Usage:
 * $rng = new \OC\Security\SecureRandom();
 * $randomString = $rng->getMediumStrengthGenerator()->generateString(30);
 *
 * @package OCP\Security
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
	 */
	public function getMediumStrengthGenerator();

	/**
	 * Generate a random string of specified length.
	 * @param string $length The length of the generated string
	 * @param string $characters An optional list of characters to use if no characterlist is
	 * 							specified all valid base64 characters except + (plus sign) are used.
	 * @return string
	 * @throws \Exception If the generator is not initialized.
	 */
	public function generate($length, $characters = '');
}
