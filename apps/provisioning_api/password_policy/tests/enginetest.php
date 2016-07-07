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

use OCA\password_policy\Engine;

class EngineTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider providesTestData
	 */
	public function testOkay(array $config, $password) {
		$default = [
			'spv_min_chars_checked' => false,
			'spv_min_chars_value' => 8,
			'spv_uppercase_checked' => false,
			'spv_uppercase_value' => 1,
			'spv_numbers_checked' => false,
			'spv_numbers_value' => 1,
			'spv_special_chars_checked' => false,
			'spv_special_chars_value' => 1,
			'spv_def_special_chars_checked' => false,
			'spv_def_special_chars_value' => '#!',
		];

		$config = array_replace($default, $config);
		$r = new Engine($config);
		$r->verifyPassword($password);
	}

	public function providesTestData() {
		return [
			[[], ''],
			[['spv_min_chars_checked' => true], '1234567890'],
			[['spv_uppercase_checked' => true], 'A234567890'],
			[['spv_numbers_checked' => true], '1234567890'],
			[['spv_special_chars_checked' => true], '#234567890'],
		];
	}
}
