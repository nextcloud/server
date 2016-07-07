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

namespace OCA\password_policy\Rules;

class Uppercase {

	public function verify($password, $val) {
		if ($this->countCapitals($password) < $val) {
			throw new \Exception("Password holds too less uppercase characters. Minimum $val uppercase characters are required.");
		}
	}

	private function countCapitals($s) {
		$split = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);

		$count = 0;
		foreach ($split as $index => $char) {
			if ($char === mb_strtoupper($char, 'UTF-8') && $char !== mb_strtolower($char, 'UTF-8')) {
				$count++;
			}
		}
		return $count;
	}

}
