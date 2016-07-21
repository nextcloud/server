<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Stefan Weil <sw@weilnetz.de>
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

use OC\Files\Mount\Manager;
use OC\Files\Mount\MountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OC\Hooks\PublicEmitter;
use OCP\Files\IRootFolder;

/**
 * Class Root
 *
 * Hooks available in scope \OC\Files
 * - preWrite(\OCP\Files\Node $node)
 * - postWrite(\OCP\Files\Node $node)
 * - preCreate(\OCP\Files\Node $node)
 * - postCreate(\OCP\Files\Node $node)
 * - preDelete(\OCP\Files\Node $node)
 * - postDelete(\OCP\Files\Node $node)
 * - preTouch(\OC\FilesP\Node $node, int $mtime)
 * - postTouch(\OCP\Files\Node $node)
 * - preCopy(\OCP\Files\Node $source, \OCP\Files\Node $target)
 * - postCopy(\OCP\Files\Node $source, \OCP\Files\Node $target)
 * - preRename(\OCP\Files\Node $source, \OCP\Files\Node $target)
 * - postRename(\OCP\Files\Node $source, \OCP\Files\Node $target)
 *
 * @package OC\Files\Node
 */
class Root extends Folder implements IRootFolder {

	/**
	 * @var \OC\Files\Mount\Manager $mountManager
	 */
	private $mountManager;

	/**
	 * @var \OC\Hooks\PublicEmitter
	 */
	private $emitter;

	/**
	 * @var \OC\User\User $user
	 */
	private $user;

	/**
	 * @param \OC\Files\Mount\Manager $manager
	 * @param \OC\Files\View $view
	 * @param \OC\User\User|null $user
	 */
	public function __construct($manager, $view, $user) {
		parent::__construct($this, $view, '');
		$this->mountManager = $manager;
		$this->user = $user;
		$this->emitter = new PublicEmitter();
	}

	/**
	 * Get the user for which the filesystem is setup
	 *
	 * @return \OC\User\User
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @param string $scope
	 * @param string $method
	 * @param callable $callback
	 */
	public function listen($scope, $method, callable $callback) {
		$this->emitter->listen($scope, $method, $callback);
	}

	/**
	 * @param string $scope optional
	 * @param string $method optional
	 * @param callable $callback optional
	 */
	public function removeListener($scope = null, $method = null, callable $callback = null) {
		$this->emitter->removeListener($scope, $method, $callback);
	}

	/**
	 * @param string $scope
	 * @param string $method
	 * @param Node[] $arguments
	 */
	public function emit($scope, $method, $arguments = array()) {
		$this->emitter->emit($scope, $method, $arguments);
	}

	/**
	 * @param \OC\Files\Storage\Storage $storage
	 * @param string $mountPoint
	 * @param array $arguments
	 */
	public function mount($storage, $mountPoint, $arguments = array()) {
		$mount = new MountPoint($storage, $mountPoint, $arguments);
		$this->mountManager->addMount($mount);
	}

	/**
	 * @param string $mountPoint
	 * @return \OC\Files\Mount\MountPoint
	 */
	public function getMount($mountPoint) {
		return $this->mountManager->find($mountPoint);
	}

	/**
	 * @param string $mountPoint
	 * @return \OC\Files\Mount\MountPoint[]
	 */
	public function getMountsIn($mountPoint) {
		return $this->mountManager->findIn($mountPoint);
	}

	/**
	 * @param string $storageId
	 * @return \OC\Files\Mount\MountPoint[]
	 */
	public function getMountByStorageId($storageId) {
		return $this->mountManager->findByStorageId($storageId);
	}

	/**
	 * @param int $numericId
	 * @return MountPoint[]
	 */
	public function getMountByNumericStorageId($numericId) {
		return $this->mountManager->findByNumericId($numericId);
	}

	/**
	 * @param \OC\Files\Mount\MountPoint $mount
	 */
	public function unMount($mount) {
		$this->mountManager->remove($mount);
	}

	/**
	 * @param string $path
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @return string
	 */
	public function get($path) {
		$path = $this->normalizePath($path);
		if ($this->isValidPath($path)) {
			$fullPath = $this->getFullPath($path);
			$fileInfo = $this->view->getFileInfo($fullPath);
			if ($fileInfo) {
				return $this->createNode($fullPath, $fileInfo);
			} else {
				throw new NotFoundException($path);
			}
		} else {
			throw new NotPermittedException();
		}
	}

	//most operations can't be done on the root

	/**
	 * @param string $targetPath
	 * @throws \OCP\Files\NotPermittedException
	 * @return \OC\Files\Node\Node
	 */
	public function rename($targetPath) {
		throw new NotPermittedException();
	}

	public function delete() {
		throw new NotPermittedException();
	}

	/**
	 * @param string $targetPath
	 * @throws \OCP\Files\NotPermittedException
	 * @return \OC\Files\Node\Node
	 */
	public function copy($targetPath) {
		throw new NotPermittedException();
	}

	/**
	 * @param int $mtime
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function touch($mtime = null) {
		throw new NotPermittedException();
	}

	/**
	 * @return \OC\Files\Storage\Storage
	 * @throws \OCP\Files\NotFoundException
	 */
	public function getStorage() {
		throw new NotFoundException();
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return '/';
	}

	/**
	 * @return string
	 */
	public function getInternalPath() {
		return '';
	}

	/**
	 * @return int
	 */
	public function getId() {
		return null;
	}

	/**
	 * @return array
	 */
	public function stat() {
		return null;
	}

	/**
	 * @return int
	 */
	public function getMTime() {
		return null;
	}

	/**
	 * @return int
	 */
	public function getSize() {
		return null;
	}

	/**
	 * @return string
	 */
	public function getEtag() {
		return null;
	}

	/**
	 * @return int
	 */
	public function getPermissions() {
		return \OCP\Constants::PERMISSION_CREATE;
	}

	/**
	 * @return bool
	 */
	public function isReadable() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function isUpdateable() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function isDeletable() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function isShareable() {
		return false;
	}

	/**
	 * @return Node
	 * @throws \OCP\Files\NotFoundException
	 */
	public function getParent() {
		throw new NotFoundException();
	}

	/**
	 * @return string
	 */
	public function getName() {
		return '';
	}

	/**
	 * Returns a view to user's files folder
	 *
	 * @param String $userId user ID
	 * @return \OCP\Files\Folder
	 */
	public function getUserFolder($userId) {
		\OC\Files\Filesystem::initMountPoints($userId);
		$dir = '/' . $userId;
		$folder = null;

		try {
			$folder = $this->get($dir);
		} catch (NotFoundException $e) {
			$folder = $this->newFolder($dir);
		}

		$dir = '/files';
		try {
			$folder = $folder->get($dir);
		} catch (NotFoundException $e) {
			$folder = $folder->newFolder($dir);
			\OC_Util::copySkeleton($userId, $folder);
		}

		return $folder;

	}
}
