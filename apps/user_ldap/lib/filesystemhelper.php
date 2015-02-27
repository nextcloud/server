<?php

/**
 * ownCloud – LDAP FilesystemHelper
 *
 * @author Arthur Schiwon
 * @copyright 2014 Arthur Schiwon blizzz@owncloud.com
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
 *
 */

namespace OCA\user_ldap\lib;

/**
 * @brief wraps around static ownCloud core methods
 */
class FilesystemHelper {

	/**
	 * @brief states whether the filesystem was loaded
	 * @return bool
	 */
	public function isLoaded() {
		return \OC\Files\Filesystem::$loaded;
	}

	/**
	 * @brief initializes the filesystem for the given user
	 * @param string the ownCloud username of the user
	 */
	public function setup($uid) {
		\OC_Util::setupFS($uid);
	}
}
