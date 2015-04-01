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

namespace OC\Encryption;

use \OC\Files\Mount;
use \OC\Files\View;

/**
 * update encrypted files, e.g. because a file was shared
 */
class Update {

	/** @var \OC\Files\View */
	protected $view;

	/** @var \OC\Encryption\Util */
	protected $util;

	 /** @var \OC\Files\Mount\Manager */
	protected $mountManager;

	/** @var \OC\Encryption\Manager */
	protected $encryptionManager;

	/** @var string */
	protected $uid;

	/** @var \OC\Encryption\File */
	protected $file;

	/**
	 *
	 * @param \OC\Files\View $view
	 * @param \OC\Encryption\Util $util
	 * @param \OC\Files\Mount\Manager $mountManager
	 * @param \OC\Encryption\Manager $encryptionManager
	 * @param \OC\Encryption\File $file
	 * @param string $uid
	 */
	public function __construct(
			View $view,
			Util $util,
			Mount\Manager $mountManager,
			Manager $encryptionManager,
			File $file,
			$uid
		) {

		$this->view = $view;
		$this->util = $util;
		$this->mountManager = $mountManager;
		$this->encryptionManager = $encryptionManager;
		$this->file = $file;
		$this->uid = $uid;
	}

	public function postShared($params) {
		if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {
			$this->update($params['fileSource']);
		}
	}

	public function postUnshared($params) {
		if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {
			$this->update($params['fileSource']);
		}
	}

	/**
	 * update keyfiles and share keys recursively
	 *
	 * @param int $fileSource file source id
	 */
	private function update($fileSource) {
		$path = \OC\Files\Filesystem::getPath($fileSource);
		$absPath = '/' . $this->uid . '/files' . $path;

		$mount = $this->mountManager->find($path);
		$mountPoint = $mount->getMountPoint();

		// if a folder was shared, get a list of all (sub-)folders
		if ($this->view->is_dir($absPath)) {
			$allFiles = $this->util->getAllFiles($absPath, $mountPoint);
		} else {
			$allFiles = array($absPath);
		}

		$encryptionModule = $this->encryptionManager->getDefaultEncryptionModule();

		foreach ($allFiles as $path) {
			$usersSharing = $this->file->getAccessList($path);
			$encryptionModule->update($absPath, $this->uid, $usersSharing);
		}
	}

}
