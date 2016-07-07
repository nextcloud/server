<?php
/**

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

use OCA\password_policy\Rules\Length;

class LengthTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException Exception
	 */
	public function testTooShort() {
		$r = new Length();
		$r->verify('1234567890', 25);
	}

	public function testOkay() {
		$r = new Length();
		$r->verify('1234567890', 6);
	}

	/**
	 * @expectedException Exception
	 */
	public function testSpecialCharsTooShort() {
		$r = new Length();
		$r->verify('ççç', 5);
	}

	public function testSpecialCharsOkay() {
		$r = new Length();
		$r->verify('çççççç', 5);
	}

}
