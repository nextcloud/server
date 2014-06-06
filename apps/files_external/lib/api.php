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
	 * Formats the given mount config to a mount entry.
	 * 
	 * @param bool $isSystemMount true for system mount, false
	 * for personal mount
	 *
	 * @return array entry
	 */
	private static function formatMount($mountConfig, $isSystemMount = false) {
		// split user name from mount point
		$path = dirname($mountConfig['mountpoint']);
		if ($path === '.') {
			$path = '';
		}

		$permissions = \OCP\PERMISSION_READ;
		// personal mounts can be deleted
		if (!$isSystemMount) {
			$permissions |= \OCP\PERMISSION_DELETE;
		}

		// TODO: add storageType, might need to use another OC_Mount_Config method
		$entry = array(
			'name' => basename($mountConfig['mountpoint']),
			'path' => $path,
			'type' => 'dir',
			'backend' => $mountConfig['backend'],
			'scope' => ( $isSystemMount ? 'system' : 'personal' ),
			'permissions' => $permissions
		);
		return $entry;
	}

	/**
	 * Returns the mount points visible for this user.
	 *
	 * @param array $params
	 * @return \OC_OCS_Result share information
	 */
	public static function getUserMounts($params) {
		$entries = array();
		$user = \OC_User::getUser();

		$personalMounts = \OC_Mount_Config::getPersonalMountPoints();
		$systemMounts = \OC_Mount_Config::getSystemMountPoints();

		foreach ($systemMounts as $mountConfig) {
			$entries[] = self::formatMount($mountConfig, true);
		}

		foreach ($personalMounts as $mountConfig) {
			$entries[] = self::formatMount($mountConfig, false);
		}

		return new \OC_OCS_Result($entries);
	}
}
