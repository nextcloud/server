<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Versions;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IUser;

class VersionManager {
	/**
	 * @var \OCP\IUser
	 */
	private $user;

	/**
	 * @var \OCP\Files\Folder
	 */
	private $versionsFolder;

	/**
	 * @var \OCA\Files_Versions\Store
	 */
	private $store;

	/**
	 * @param \OCP\Files\Folder $rootFolder
	 * @param \OCP\IUser $user
	 */
	public function __construct(Folder $rootFolder, IUser $user) {
		$this->user = $user;
		$this->versionsFolder = $rootFolder->get('/' . $user->getUID() . '/files_versions');
		/** @var \OCP\Files\Folder $userFolder */
		$userFolder = $rootFolder->get('/' . $user->getUID() . '/files');
		$this->store = new Store($this->versionsFolder, $userFolder);
	}

	/**
	 * Handle rename of a file
	 *
	 * @param \OCP\Files\Node $source
	 * @param \OCP\Files\Node $target
	 */
	public function onRename(Node $source, Node $target) {
		if ($source instanceof File and $target instanceof File) {
			$this->store->renameVersions($source, $target);
		} else {
			//TODO
		}
	}

	/**
	 * Handle copy of a file
	 *
	 * @param \OCP\Files\Node $source
	 * @param \OCP\Files\Node $target
	 */
	public function onCopy(Node $source, Node $target) {
		if ($source instanceof File and $target instanceof File) {
			$this->store->copyVersions($source, $target);
		} else {
			//TODO
		}
	}

	/**
	 * @param \OCP\Files\Node $file
	 */
	public function onWrite(Node $file) {
		if ($file instanceof File and $file->getSize() > 0) {
			$this->store->newVersion($file);
		}
	}

	public function onDelete(Node $file) {
		if ($file instanceof File) {
			$this->store->removeForFile($file);
		} else {
			//TODO
		}
	}
}
