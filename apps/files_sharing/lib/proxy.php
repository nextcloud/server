<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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
