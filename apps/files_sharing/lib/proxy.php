<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2014 Bjoern Schiessle <schiessle@owncloud.com>
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

namespace OCA\Files\Share;

class Proxy extends \OC_FileProxy {

	/**
	 * check if the deleted folder contains share mount points and move them
	 * up to the parent
	 *
	 * @param string $path
	 */
	public function preUnlink($path) {
		$this->moveMountPointsUp($path);
	}

	/**
	 * check if the deleted folder contains share mount points and move them
	 * up to the parent
	 *
	 * @param string $path
	 */
	public function preRmdir($path) {
		$this->moveMountPointsUp($path);
	}

	/**
	 * move share mount points up to the parent
	 *
	 * @param string $path
	 */
	private function moveMountPointsUp($path) {
		$view = new \OC\Files\View('/');

		// find share mount points within $path and move them up to the parent folder
		// before we delete $path
		$mountManager = \OC\Files\Filesystem::getMountManager();
		$mountedShares = $mountManager->findIn($path);
		foreach ($mountedShares as $mount) {
			if ($mount->getStorage()->instanceOfStorage('\OC\Files\Storage\Shared')) {
				$mountPoint = $mount->getMountPoint();
				$mountPointName = $mount->getMountPointName();
				$target = \OCA\Files_Sharing\Helper::generateUniqueTarget(dirname($path) . '/' . $mountPointName, array(), $view);
				$view->rename($mountPoint, $target);
			}
		}
	}

}
