<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
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
		$info = \OC\Files\Filesystem::getFileInfo($path);
		$owner = \OC\Files\Filesystem::getOwner($path);
		$view = new \OC\Files\View('/' . $owner . '/files');
		$ownerPath = $view->getPath($info->getId());
		$absPath = '/' . $owner . '/files' . $ownerPath;

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
			$encryptionModule->update($path, $this->uid, $usersSharing);
		}
	}

}
