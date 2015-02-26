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
use OCA\Files_Sharing\Helper;

class Proxy extends \OC_FileProxy {

	/**
	 * check if the deleted folder contains share mount points and unshare them
	 *
	 * @param string $path
	 */
	public function preUnlink($path) {
		$this->unshareChildren($path);
	}

	/**
	 * check if the deleted folder contains share mount points and unshare them
	 *
	 * @param string $path
	 */
	public function preRmdir($path) {
		$this->unshareChildren($path);
	}

	/**
	 * unshare shared items below the deleted folder
	 *
	 * @param string $path
	 */
	private function unshareChildren($path) {
		$view = new \OC\Files\View('/');

		// find share mount points within $path and unmount them
		$mountManager = \OC\Files\Filesystem::getMountManager();
		$mountedShares = $mountManager->findIn($path);
		foreach ($mountedShares as $mount) {
			if ($mount->getStorage()->instanceOfStorage('OCA\Files_Sharing\ISharedStorage')) {
				$mountPoint = $mount->getMountPoint();
				$view->unlink($mountPoint);
			}
		}
	}

}
