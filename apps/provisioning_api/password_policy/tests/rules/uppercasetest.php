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

use OCA\password_policy\Rules\Uppercase;

class UppercaseTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException Exception
	 */
	public function testTooShort() {
		$r = new Uppercase();
		$r->verify('ab', 4);
	}

	public function testOkay() {
		$r = new Uppercase();
		$r->verify('ABCFWA12345', 6);
	}

	/**
	 * @expectedException Exception
	 */
	public function testSpecialUpperCaseTooShort() {
		$r = new Uppercase();
		$r->verify('Ññññññññññ', 5);
	}

	public function testSpecialUpperCaseOkay() {
		$r = new Uppercase();
		$r->verify('ÑñÑñÑñÑñÑñ', 5);
	}

	/**
	 * @expectedException Exception
	 */
	public function testNumericOnlyTooShort() {
		$r = new Uppercase();
		$r->verify('11111111', 5);
	}

	/**
	 * @expectedException Exception
	 */
	public function testSpecialOnlyTooShort() {
		$r = new Uppercase();
		$r->verify('#+?@#+?@', 5);
	}

}
