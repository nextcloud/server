<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;
use OCP\Mail\Util;

/**
 * Class Util
 *
 * @package OC\Mail
 */
class UtilTest extends TestCase {

	/**
	 * @return array
	 */
	public function mailAddressProvider() {
		return array(
			array('lukas@owncloud.com', true),
			array('lukas@localhost', true),
			array('lukas@192.168.1.1', true),
			array('lukas@éxämplè.com', true),
			array('asdf', false),
			array('lukas@owncloud.org@owncloud.com', false)
		);
	}

	/**
	 * @dataProvider mailAddressProvider
	 */
	public function testValidateMailAddress($email, $expected) {
		$this->assertSame($expected, Util::validateMailAddress($email));
	}
}
