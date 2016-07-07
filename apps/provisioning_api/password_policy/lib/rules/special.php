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

class Special {

	/**
	 * @param string $password
	 * @param string $val
	 * @param string $allowedSpecialChars
	 * @throws \Exception
	 */
	public function verify($password, $val, $allowedSpecialChars) {
		$special = $this->stripAlphaNumeric($password);
		if (!empty($allowedSpecialChars) && !empty($special)) {
			$allowedSpecialCharsAsArray = str_split($allowedSpecialChars);
			$s = array_filter(str_split($special), function($char) use ($allowedSpecialCharsAsArray){
				return !(in_array($char, $allowedSpecialCharsAsArray, true));
			});
			if (count($s) > 0) {
				throw new \Exception("Password holds invalid special characters. Only $allowedSpecialChars as allowed");
			}
		}
		if (strlen($special) < $val) {
			throw new \Exception("Password holds too less special characters. Minimum $val special characters are required.");
		}
	}

	private function stripAlphaNumeric( $string ) {
		return preg_replace( "/[a-z0-9]/i", "", $string );
	}

}

