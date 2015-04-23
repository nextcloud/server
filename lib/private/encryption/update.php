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

use OC\Files\Filesystem;
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

	/**
	 * hook after file was shared
	 *
	 * @param array $params
	 */
	public function postShared($params) {
		if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {
			$path = Filesystem::getPath($params['fileSource']);
			list($owner, $ownerPath) = $this->getOwnerPath($path);
			$absPath = '/' . $owner . '/files/' . $ownerPath;
			$this->update($absPath);
		}
	}

	/**
	 * hook after file was unshared
	 *
	 * @param array $params
	 */
	public function postUnshared($params) {
		if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {
			$path = Filesystem::getPath($params['fileSource']);
			list($owner, $ownerPath) = $this->getOwnerPath($path);
			$absPath = '/' . $owner . '/files/' . $ownerPath;
			$this->update($absPath);
		}
	}

	/**
	 * get owner and path relative to data/<owner>/files
	 *
	 * @param string $path path to file for current user
	 * @return array ['owner' => $owner, 'path' => $path]
	 * @throw \InvalidArgumentException
	 */
	private function getOwnerPath($path) {
		$info = Filesystem::getFileInfo($path);
		$owner = Filesystem::getOwner($path);
		$view = new View('/' . $owner . '/files');
		$path = $view->getPath($info->getId());
		if ($path === null) {
			throw new \InvalidArgumentException('No file found for ' . $info->getId());
		}

		return array($owner, $path);
	}

	/**
	 * notify encryption module about added/removed users from a file/folder
	 *
	 * @param string $path relative to data/
	 * @throws Exceptions\ModuleDoesNotExistsException
	 */
	public function update($path) {

		// if a folder was shared, get a list of all (sub-)folders
		if ($this->view->is_dir($path)) {
			$allFiles = $this->util->getAllFiles($path);
		} else {
			$allFiles = array($path);
		}

		$encryptionModule = $this->encryptionManager->getDefaultEncryptionModule();

		foreach ($allFiles as $file) {
			$usersSharing = $this->file->getAccessList($file);
			$encryptionModule->update($file, $this->uid, $usersSharing);
		}
	}

}
