<?php
/**
 * ownCloud
 *
 * @author Vincent Petry
 * @copyright 2014 Vincent Petry pvince81@owncloud.com
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

namespace OCA\Files\External;

class Api {

	/**
	 * Returns the mount points visible for this user.
	 *
	 * @param array $params
	 * @return \OC_OCS_Result share information
	 */
	public static function getUserMounts($params) {
		$entries = array();
		$user = \OC_User::getUser();
		$mounts = \OC_Mount_Config::getAbsoluteMountPoints($user);

		foreach ($mounts as $mountPoint => $config) {
			// split user name from mount point
			$parts = explode('/', ltrim($mountPoint, '/'));
			array_shift($parts); // use name
			array_shift($parts); // files
			$mountPoint = implode('/', $parts);

			$path = dirname($mountPoint);
			if ($path === '.') {
				$path = '';
			}

			// TODO: give delete permissions if mount type is personal
			$permissions = \OCP\PERMISSION_READ;

			// TODO: add storageType, might need to use another OC_Mount_Config method
			$entries[] = array(
				'name' => basename($mountPoint),
				'path' => $path,
				'type' => 'dir',
				'permissions' => $permissions
			);
		}

		return new \OC_OCS_Result($entries);
	}
}
