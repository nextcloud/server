<?php
/**
 * @author Robin McCorkell <rmccorkell@owncloud.com>
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

namespace OCA\Files_External\Lib;

use \OCA\Files_External\Lib\MissingDependency;

/**
 * Trait for objects that have dependencies for use
 */
trait DependencyTrait {

	/** @var callable|null dependency check */
	private $dependencyCheck = null;

	/**
	 * @return bool
	 */
	public function hasDependencies() {
		return !is_null($this->dependencyCheck);
	}

	/**
	 * @param callable $dependencyCheck
	 * @return self
	 */
	public function setDependencyCheck(callable $dependencyCheck) {
		$this->dependencyCheck = $dependencyCheck;
		return $this;
	}

	/**
	 * Check if object is valid for use
	 *
	 * @return MissingDependency[] Unsatisfied dependencies
	 */
	public function checkDependencies() {
		$ret = [];

		if ($this->hasDependencies()) {
			$result = call_user_func($this->dependencyCheck);
			if ($result !== true) {
				if (!is_array($result)) {
					$result = [$result];
				}
				foreach ($result as $key => $value) {
					if (!($value instanceof MissingDependency)) {
						$module = null;
						$message = null;
						if (is_numeric($key)) {
							$module = $value;
						} else {
							$module = $key;
							$message = $value;
						}
						$value = new MissingDependency($module, $this);
						$value->setMessage($message);
					}
					$ret[] = $value;
				}
			}
		}

		return $ret;
	}

}

