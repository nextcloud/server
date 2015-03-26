<?php

/**
 * ownCloud
 *
 * @copyright (C) 2015 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\Encryption\Keys;

use OC\Encryption\Util;
use OC\Files\View;
use OC\User;

/**
 * Factory provides KeyStorage for different encryption modules
 */
class Factory {
	/** @var array */
	protected $instances = array();

	/**
	 * get a KeyStorage instance
	 *
	 * @param string $encryptionModuleId
	 * @param View $view
	 * @param Util $util
	 * @return Storage
	 */
	public function get($encryptionModuleId,View $view, Util $util) {
		if (!isset($this->instances[$encryptionModuleId])) {
			$this->instances[$encryptionModuleId] = new Storage($encryptionModuleId, $view, $util);
		}
		return $this->instances[$encryptionModuleId];
	}

}
