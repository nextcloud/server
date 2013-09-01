<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Node;

use OC\Files\Cache\Cache;
use OC\Files\Cache\Scanner;
use OC\Files\Mount\Manager;
use OC\Files\Mount\Mount;
use OC\Files\NotFoundException;
use OC\Files\NotPermittedException;
use OC\Hooks\Emitter;
use OC\Hooks\PublicEmitter;

/**
 * Class Root
 *
 * Hooks available in scope \OC\Files
 * - preWrite(\OC\Files\Node\Node $node)
 * - postWrite(\OC\Files\Node\Node $node)
 * - preCreate(\OC\Files\Node\Node $node)
 * - postCreate(\OC\Files\Node\Node $node)
 * - preDelete(\OC\Files\Node\Node $node)
 * - postDelete(\OC\Files\Node\Node $node)
 * - preTouch(\OC\Files\Node\Node $node, int $mtime)
 * - postTouch(\OC\Files\Node\Node $node)
 * - preCopy(\OC\Files\Node\Node $source, \OC\Files\Node\Node $target)
 * - postCopy(\OC\Files\Node\Node $source, \OC\Files\Node\Node $target)
 * - preRename(\OC\Files\Node\Node $source, \OC\Files\Node\Node $target)
 * - postRename(\OC\Files\Node\Node $source, \OC\Files\Node\Node $target)
 *
 * @package OC\Files\Node
 */
class Root extends Folder implements Emitter {

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
	 * @param \OC\User\User $user
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
	public function listen($scope, $method, $callback) {
		$this->emitter->listen($scope, $method, $callback);
	}

	/**
	 * @param string $scope optional
	 * @param string $method optional
	 * @param callable $callback optional
	 */
	public function removeListener($scope = null, $method = null, $callback = null) {
		$this->emitter->removeListener($scope, $method, $callback);
	}

	/**
	 * @param string $scope
	 * @param string $method
	 * @param array $arguments
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
		$mount = new Mount($storage, $mountPoint, $arguments);
		$this->mountManager->addMount($mount);
	}

	/**
	 * @param string $mountPoint
	 * @return \OC\Files\Mount\Mount
	 */
	public function getMount($mountPoint) {
		return $this->mountManager->find($mountPoint);
	}

	/**
	 * @param string $mountPoint
	 * @return \OC\Files\Mount\Mount[]
	 */
	public function getMountsIn($mountPoint) {
		return $this->mountManager->findIn($mountPoint);
	}

	/**
	 * @param string $storageId
	 * @return \OC\Files\Mount\Mount[]
	 */
	public function getMountByStorageId($storageId) {
		return $this->mountManager->findByStorageId($storageId);
	}

	/**
	 * @param int $numericId
	 * @return Mount[]
	 */
	public function getMountByNumericStorageId($numericId) {
		return $this->mountManager->findByNumericId($numericId);
	}

	/**
	 * @param \OC\Files\Mount\Mount $mount
	 */
	public function unMount($mount) {
		$this->mountManager->remove($mount);
	}

	/**
	 * @param string $path
	 * @throws \OC\Files\NotFoundException
	 * @throws \OC\Files\NotPermittedException
	 * @return Node
	 */
	public function get($path) {
		$path = $this->normalizePath($path);
		if ($this->isValidPath($path)) {
			$fullPath = $this->getFullPath($path);
			if ($this->view->file_exists($fullPath)) {
				return $this->createNode($fullPath);
			} else {
				throw new NotFoundException();
			}
		} else {
			throw new NotPermittedException();
		}
	}

	/**
	 * search file by id
	 *
	 * An array is returned because in the case where a single storage is mounted in different places the same file
	 * can exist in different places
	 *
	 * @param int $id
	 * @throws \OC\Files\NotFoundException
	 * @return Node[]
	 */
	public function getById($id) {
		$result = Cache::getById($id);
		if (is_null($result)) {
			throw new NotFoundException();
		} else {
			list($storageId, $internalPath) = $result;
			$nodes = array();
			$mounts = $this->mountManager->findByStorageId($storageId);
			foreach ($mounts as $mount) {
				$nodes[] = $this->get($mount->getMountPoint() . $internalPath);
			}
			return $nodes;
		}

	}

	//most operations cant be done on the root

	/**
	 * @param string $targetPath
	 * @throws \OC\Files\NotPermittedException
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
	 * @throws \OC\Files\NotPermittedException
	 * @return \OC\Files\Node\Node
	 */
	public function copy($targetPath) {
		throw new NotPermittedException();
	}

	/**
	 * @param int $mtime
	 * @throws \OC\Files\NotPermittedException
	 */
	public function touch($mtime = null) {
		throw new NotPermittedException();
	}

	/**
	 * @return \OC\Files\Storage\Storage
	 * @throws \OC\Files\NotFoundException
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
		return \OCP\PERMISSION_CREATE;
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
	 * @throws \OC\Files\NotFoundException
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
}
