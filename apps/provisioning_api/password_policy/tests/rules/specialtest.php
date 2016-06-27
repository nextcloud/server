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

use OCA\password_policy\Rules\Special;

class SpecialTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException Exception
	 */
	public function testTooShort() {
		$r = new Special();
		$r->verify('', 4, []);
	}

	/**
	 * @dataProvider providesTestData
	 */
	public function testOkay($password, $val, $allowedSpecialChars) {
		$r = new Special();
		$r->verify($password, $val, $allowedSpecialChars);
	}

	/**
	 * @dataProvider providesExceptionalData
	 */
	public function testInvalidSpecial($expectedMessage, $password, $val, $allowedSpecialChars) {
		try {
			$r = new Special();
			$r->verify($password, $val, $allowedSpecialChars);
			$this->fail('');
		} catch (Exception $ex) {
			$this->assertEquals($expectedMessage, $ex->getMessage());
		}
	}

	function providesExceptionalData() {
		return [
			['Password holds invalid special characters. Only #+ as allowed', '#+?@#+?@', 4, '#+'],
			['Password holds too less special characters. Minimum 9 special characters are required.', '#+?@#+?@', 9, []],
			['Password holds too less special characters. Minimum 10 special characters are required.', '#+?@#+?@', 10, '#+?@'],
			['Password holds too less special characters. Minimum 2 special characters are required.', '#', 2, '#!'],
			['Password holds too less special characters. Minimum 1 special characters are required.', 'qaa', 1, '#!']
		];
	}

	function providesTestData() {
		return [
			['#+?@#+?@', 6, []],
			['#+?@#+?@', 6, '#+?@'],
			['#', 1, '#!']
		];
	}
}
