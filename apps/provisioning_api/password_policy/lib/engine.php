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

namespace OCA\password_policy;

use OCA\password_policy\Rules\Length;
use OCA\password_policy\Rules\Numbers;
use OCA\password_policy\Rules\Special;
use OCA\password_policy\Rules\Uppercase;

class Engine {

	private $configValues;

	public function __construct(array $configValues) {
		$this->configValues = $configValues;
	}

	public function verifyPassword($password) {
		if ($this->yes('spv_min_chars_checked')) {
			$val = $this->configValues['spv_min_chars_value'];
			$r = new Length();
			$r->verify($password, $val);
		}
		if ($this->yes('spv_uppercase_checked')) {
			$val = $this->configValues['spv_uppercase_value'];
			$r = new Uppercase();
			$r->verify($password, $val);
		}
		if ($this->yes('spv_numbers_checked')) {
			$val = $this->configValues['spv_numbers_value'];
			$r = new Numbers();
			$r->verify($password, $val);
		}
		if ($this->yes('spv_special_chars_checked')) {
			$val = $this->configValues['spv_special_chars_value'];
			$chars = [];
			if ($this->yes('spv_def_special_chars_checked')) {
				$chars = $this->configValues['spv_def_special_chars_value'];
			}
			$r = new Special();
			$r->verify($password, $val, $chars);
		}
	}

	/**
	 * @return bool
	 */
	private function yes($key) {
		if ($this->configValues[$key] === 'on') {
			return true;
		}
		return $this->configValues[$key];
	}
}

