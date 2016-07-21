<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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

namespace OC\Files\Node;

use OC\Files\Filesystem;
use OCP\Files\FileInfo;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class Node implements \OCP\Files\Node {
	/**
	 * @var \OC\Files\View $view
	 */
	protected $view;

	/**
	 * @var \OC\Files\Node\Root $root
	 */
	protected $root;

	/**
	 * @var string $path
	 */
	protected $path;

	/**
	 * @var \OCP\Files\FileInfo
	 */
	protected $fileInfo;

	/**
	 * @param \OC\Files\View $view
	 * @param \OC\Files\Node\Root $root
	 * @param string $path
	 * @param FileInfo $fileInfo
	 */
	public function __construct($root, $view, $path, $fileInfo = null) {
		$this->view = $view;
		$this->root = $root;
		$this->path = $path;
		$this->fileInfo = $fileInfo;
	}

	/**
	 * Returns the matching file info
	 *
	 * @return FileInfo
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getFileInfo() {
		if (!Filesystem::isValidPath($this->path)) {
			throw new InvalidPathException();
		}
		if (!$this->fileInfo) {
			$fileInfo = $this->view->getFileInfo($this->path);
			if ($fileInfo instanceof FileInfo) {
				$this->fileInfo = $fileInfo;
			} else {
				throw new NotFoundException();
			}
		}
		return $this->fileInfo;
	}

	/**
	 * @param string[] $hooks
	 */
	protected function sendHooks($hooks) {
		foreach ($hooks as $hook) {
			$this->root->emit('\OC\Files', $hook, array($this));
		}
	}

	/**
	 * @param int $permissions
	 * @return bool
	 */
	protected function checkPermissions($permissions) {
		return ($this->getPermissions() & $permissions) === $permissions;
	}

	/**
	 * @param string $targetPath
	 * @throws \OCP\Files\NotPermittedException
	 * @return \OC\Files\Node\Node
	 */
	public function move($targetPath) {
		return;
	}

	public function delete() {
		return;
	}

	/**
	 * @param string $targetPath
	 * @return \OC\Files\Node\Node
	 */
	public function copy($targetPath) {
		return;
	}

	/**
	 * @param int $mtime
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function touch($mtime = null) {
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_UPDATE)) {
			$this->sendHooks(array('preTouch'));
			$this->view->touch($this->path, $mtime);
			$this->sendHooks(array('postTouch'));
			if ($this->fileInfo) {
				if (is_null($mtime)) {
					$mtime = time();
				}
				$this->fileInfo['mtime'] = $mtime;
			}
		} else {
			throw new NotPermittedException();
		}
	}

	/**
	 * @return \OC\Files\Storage\Storage
	 * @throws \OCP\Files\NotFoundException
	 */
	public function getStorage() {
		list($storage,) = $this->view->resolvePath($this->path);
		return $storage;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getInternalPath() {
		list(, $internalPath) = $this->view->resolvePath($this->path);
		return $internalPath;
	}

	/**
	 * @return int
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getId() {
		return $this->getFileInfo()->getId();
	}

	/**
	 * @return array
	 */
	public function stat() {
		return $this->view->stat($this->path);
	}

	/**
	 * @return int
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getMTime() {
		return $this->getFileInfo()->getMTime();
	}

	/**
	 * @return int
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getSize() {
		return $this->getFileInfo()->getSize();
	}

	/**
	 * @return string
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getEtag() {
		return $this->getFileInfo()->getEtag();
	}

	/**
	 * @return int
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getPermissions() {
		return $this->getFileInfo()->getPermissions();
	}

	/**
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function isReadable() {
		return $this->getFileInfo()->isReadable();
	}

	/**
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function isUpdateable() {
		return $this->getFileInfo()->isUpdateable();
	}

	/**
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function isDeletable() {
		return $this->getFileInfo()->isDeletable();
	}

	/**
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function isShareable() {
		return $this->getFileInfo()->isShareable();
	}

	/**
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function isCreatable() {
		return $this->getFileInfo()->isCreatable();
	}

	/**
	 * @return Node
	 */
	public function getParent() {
		return $this->root->get(dirname($this->path));
	}

	/**
	 * @return string
	 */
	public function getName() {
		return basename($this->path);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function normalizePath($path) {
		if ($path === '' or $path === '/') {
			return '/';
		}
		//no windows style slashes
		$path = str_replace('\\', '/', $path);
		//add leading slash
		if ($path[0] !== '/') {
			$path = '/' . $path;
		}
		//remove duplicate slashes
		while (strpos($path, '//') !== false) {
			$path = str_replace('//', '/', $path);
		}
		//remove trailing slash
		$path = rtrim($path, '/');

		return $path;
	}

	/**
	 * check if the requested path is valid
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isValidPath($path) {
		if (!$path || $path[0] !== '/') {
			$path = '/' . $path;
		}
		if (strstr($path, '/../') || strrchr($path, '/') === '/..') {
			return false;
		}
		return true;
	}

	public function isMounted() {
		return $this->getFileInfo()->isMounted();
	}

	public function isShared() {
		return $this->getFileInfo()->isShared();
	}

	public function getMimeType() {
		return $this->getFileInfo()->getMimetype();
	}

	public function getMimePart() {
		return $this->getFileInfo()->getMimePart();
	}

	public function getType() {
		return $this->getFileInfo()->getType();
	}

	public function isEncrypted() {
		return $this->getFileInfo()->isEncrypted();
	}

	public function getMountPoint() {
		return $this->getFileInfo()->getMountPoint();
	}

	public function getOwner() {
		return $this->getFileInfo()->getOwner();
	}

	public function getChecksum() {
		return;
	}

	/**
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @throws \OCP\Lock\LockedException
	 */
	public function lock($type) {
		$this->view->lockFile($this->path, $type);
	}

	/**
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @throws \OCP\Lock\LockedException
	 */
	public function changeLock($type) {
		$this->view->changeLock($this->path, $type);
	}

	/**
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @throws \OCP\Lock\LockedException
	 */
	public function unlock($type) {
		$this->view->unlockFile($this->path, $type);
	}
}
