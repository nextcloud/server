<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OC\L10N;

/**
 * TODO: Description
 */
class Factory {
	/**
	 * cached instances
	 */
	protected $instances = array();

	/**
	 * get an L10N instance
	 *
	 * @param string $app
	 * @param string|null $lang
	 * @return \OC_L10N
	 */
	public function get($app, $lang = null) {
		if (!is_null($lang)) {
			return new \OC_L10N($app, $lang);
		} else if (!isset($this->instances[$app])) {
			$this->instances[$app] = new \OC_L10N($app);
		}
		return $this->instances[$app];
	}

}
