<?php
/**
 * @author Jesús Macias <jmacias@solidgear.es>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\Files_External\Lib;

class Api {

	/**
	 * Formats the given mount config to a mount entry.
	 *
	 * @param string $mountPoint mount point name, relative to the data dir
	 * @param array $mountConfig mount config to format
	 *
	 * @return array entry
	 */
	private static function formatMount($mountPoint, $mountConfig) {
		// strip "/$user/files" from mount point
		$mountPoint = explode('/', trim($mountPoint, '/'), 3);
		$mountPoint = $mountPoint[2];

		// split path from mount point
		$path = dirname($mountPoint);
		if ($path === '.') {
			$path = '';
		}

		$isSystemMount = !$mountConfig['personal'];

		$permissions = \OCP\Constants::PERMISSION_READ;
		// personal mounts can be deleted
		if (!$isSystemMount) {
			$permissions |= \OCP\Constants::PERMISSION_DELETE;
		}

		$entry = array(
			'name' => basename($mountPoint),
			'path' => $path,
			'type' => 'dir',
			'backend' => $mountConfig['backend'],
			'scope' => ( $isSystemMount ? 'system' : 'personal' ),
			'permissions' => $permissions,
			'id' => $mountConfig['id'],
			'class' => $mountConfig['class']
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
		$user = \OC::$server->getUserSession()->getUser()->getUID();

		$mounts = \OC_Mount_Config::getAbsoluteMountPoints($user);
		foreach($mounts as $mountPoint => $mount) {
			$entries[] = self::formatMount($mountPoint, $mount);
		}

		return new \OC_OCS_Result($entries);
	}
}
