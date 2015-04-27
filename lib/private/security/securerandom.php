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

use RandomLib;
use Sabre\DAV\Exception;
use OCP\Security\ISecureRandom;

/**
 * Class SecureRandom provides a layer around RandomLib to generate
 * secure random strings.
 *
 * Usage:
 * \OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate(10);
 *
 * @package OC\Security
 */
class SecureRandom implements ISecureRandom {

	/** @var \RandomLib\Factory */
	var $factory;
	/** @var \RandomLib\Generator */
	var $generator;

	function __construct() {
		$this->factory = new RandomLib\Factory;
	}

	/**
	 * Convenience method to get a low strength random number generator.
	 *
	 * Low Strength should be used anywhere that random strings are needed
	 * in a non-cryptographical setting. They are not strong enough to be
	 * used as keys or salts. They are however useful for one-time use tokens.
	 *
	 * @return $this
	 */
	public function getLowStrengthGenerator() {
		$this->generator = $this->factory->getLowStrengthGenerator();
		return $this;
	}

	/**
	 * Convenience method to get a medium strength random number generator.
	 *
	 * Medium Strength should be used for most needs of a cryptographic nature.
	 * They are strong enough to be used as keys and salts. However, they do
	 * take some time and resources to generate, so they should not be over-used
	 *
	 * @return $this
	 */
	public function getMediumStrengthGenerator() {
		$this->generator = $this->factory->getMediumStrengthGenerator();
		return $this;
	}

	/**
	 * Generate a random string of specified length.
	 * @param int $length The length of the generated string
	 * @param string $characters An optional list of characters to use if no characterlist is
	 * 							specified all valid base64 characters are used.
	 * @return string
	 * @throws \Exception If the generator is not initialized.
	 */
	public function generate($length, $characters = '') {
		if(is_null($this->generator)) {
			throw new \Exception('Generator is not initialized.');
		}

		return $this->generator->generateString($length, $characters);
	}
}
