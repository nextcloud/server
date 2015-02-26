<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Node;

use OC\Files\Cache\Cache;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class Folder extends Node implements \OCP\Files\Folder {
	/**
	 * @param string $path path relative to the folder
	 * @return string
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function getFullPath($path) {
		if (!$this->isValidPath($path)) {
			throw new NotPermittedException();
		}
		return $this->path . $this->normalizePath($path);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function getRelativePath($path) {
		if ($this->path === '' or $this->path === '/') {
			return $this->normalizePath($path);
		}
		if ($path === $this->path) {
			return '/';
		} else if (strpos($path, $this->path . '/') !== 0) {
			return null;
		} else {
			$path = substr($path, strlen($this->path));
			return $this->normalizePath($path);
		}
	}

	/**
	 * check if a node is a (grand-)child of the folder
	 *
	 * @param \OC\Files\Node\Node $node
	 * @return bool
	 */
	public function isSubNode($node) {
		return strpos($node->getPath(), $this->path . '/') === 0;
	}

	/**
	 * get the content of this directory
	 *
	 * @throws \OCP\Files\NotFoundException
	 * @return Node[]
	 */
	public function getDirectoryListing() {
		$result = array();

		/**
		 * @var \OC\Files\Storage\Storage $storage
		 */
		list($storage, $internalPath) = $this->view->resolvePath($this->path);
		if ($storage) {
			$cache = $storage->getCache($internalPath);

			//trigger cache update check
			$this->view->getFileInfo($this->path);

			$files = $cache->getFolderContents($internalPath);
		} else {
			$files = array();
		}

		//add a folder for any mountpoint in this directory and add the sizes of other mountpoints to the folders
		$mounts = $this->root->getMountsIn($this->path);
		$dirLength = strlen($this->path);
		foreach ($mounts as $mount) {
			$subStorage = $mount->getStorage();
			if ($subStorage) {
				$subCache = $subStorage->getCache('');

				if ($subCache->getStatus('') === Cache::NOT_FOUND) {
					$subScanner = $subStorage->getScanner('');
					$subScanner->scanFile('');
				}

				$rootEntry = $subCache->get('');
				if ($rootEntry) {
					$relativePath = trim(substr($mount->getMountPoint(), $dirLength), '/');
					if ($pos = strpos($relativePath, '/')) {
						//mountpoint inside subfolder add size to the correct folder
						$entryName = substr($relativePath, 0, $pos);
						foreach ($files as &$entry) {
							if ($entry['name'] === $entryName) {
								if ($rootEntry['size'] >= 0) {
									$entry['size'] += $rootEntry['size'];
								} else {
									$entry['size'] = -1;
								}
							}
						}
					} else { //mountpoint in this folder, add an entry for it
						$rootEntry['name'] = $relativePath;
						$rootEntry['storageObject'] = $subStorage;

						//remove any existing entry with the same name
						foreach ($files as $i => $file) {
							if ($file['name'] === $rootEntry['name']) {
								$files[$i] = null;
								break;
							}
						}
						$files[] = $rootEntry;
					}
				}
			}
		}

		foreach ($files as $file) {
			if ($file) {
				$node = $this->createNode($this->path . '/' . $file['name'], $file);
				$result[] = $node;
			}
		}

		return $result;
	}

	/**
	 * @param string $path
	 * @param array $info
	 * @return File|Folder
	 */
	protected function createNode($path, $info = array()) {
		if (!isset($info['mimetype'])) {
			$isDir = $this->view->is_dir($path);
		} else {
			$isDir = $info['mimetype'] === 'httpd/unix-directory';
		}
		if ($isDir) {
			return new Folder($this->root, $this->view, $path);
		} else {
			return new File($this->root, $this->view, $path);
		}
	}

	/**
	 * Get the node at $path
	 *
	 * @param string $path
	 * @return \OC\Files\Node\Node
	 * @throws \OCP\Files\NotFoundException
	 */
	public function get($path) {
		return $this->root->get($this->getFullPath($path));
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	public function nodeExists($path) {
		try {
			$this->get($path);
			return true;
		} catch (NotFoundException $e) {
			return false;
		}
	}

	/**
	 * @param string $path
	 * @return \OC\Files\Node\Folder
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function newFolder($path) {
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_CREATE)) {
			$fullPath = $this->getFullPath($path);
			$nonExisting = new NonExistingFolder($this->root, $this->view, $fullPath);
			$this->root->emit('\OC\Files', 'preWrite', array($nonExisting));
			$this->root->emit('\OC\Files', 'preCreate', array($nonExisting));
			$this->view->mkdir($fullPath);
			$node = new Folder($this->root, $this->view, $fullPath);
			$this->root->emit('\OC\Files', 'postWrite', array($node));
			$this->root->emit('\OC\Files', 'postCreate', array($node));
			return $node;
		} else {
			throw new NotPermittedException();
		}
	}

	/**
	 * @param string $path
	 * @return \OC\Files\Node\File
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function newFile($path) {
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_CREATE)) {
			$fullPath = $this->getFullPath($path);
			$nonExisting = new NonExistingFile($this->root, $this->view, $fullPath);
			$this->root->emit('\OC\Files', 'preWrite', array($nonExisting));
			$this->root->emit('\OC\Files', 'preCreate', array($nonExisting));
			$this->view->touch($fullPath);
			$node = new File($this->root, $this->view, $fullPath);
			$this->root->emit('\OC\Files', 'postWrite', array($node));
			$this->root->emit('\OC\Files', 'postCreate', array($node));
			return $node;
		} else {
			throw new NotPermittedException();
		}
	}

	/**
	 * search for files with the name matching $query
	 *
	 * @param string $query
	 * @return \OC\Files\Node\Node[]
	 */
	public function search($query) {
		return $this->searchCommon('search', array('%' . $query . '%'));
	}

	/**
	 * search for files by mimetype
	 *
	 * @param string $mimetype
	 * @return Node[]
	 */
	public function searchByMime($mimetype) {
		return $this->searchCommon('searchByMime', array($mimetype));
	}

	/**
	 * search for files by tag
	 *
	 * @param string|int $tag name or tag id
	 * @param string $userId owner of the tags
	 * @return Node[]
	 */
	public function searchByTag($tag, $userId) {
		return $this->searchCommon('searchByTag', array($tag, $userId));
	}

	/**
	 * @param string $method cache method
	 * @param array $args call args
	 * @return \OC\Files\Node\Node[]
	 */
	private function searchCommon($method, $args) {
		$files = array();
		$rootLength = strlen($this->path);
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 */
		list($storage, $internalPath) = $this->view->resolvePath($this->path);
		$internalPath = rtrim($internalPath, '/') . '/';
		$internalRootLength = strlen($internalPath);

		$cache = $storage->getCache('');

		$results = call_user_func_array(array($cache, $method), $args);
		foreach ($results as $result) {
			if ($internalRootLength === 0 or substr($result['path'], 0, $internalRootLength) === $internalPath) {
				$result['internalPath'] = $result['path'];
				$result['path'] = substr($result['path'], $internalRootLength);
				$result['storage'] = $storage;
				$files[] = $result;
			}
		}

		$mounts = $this->root->getMountsIn($this->path);
		foreach ($mounts as $mount) {
			$storage = $mount->getStorage();
			if ($storage) {
				$cache = $storage->getCache('');

				$relativeMountPoint = substr($mount->getMountPoint(), $rootLength);
				$results = call_user_func_array(array($cache, $method), $args);
				foreach ($results as $result) {
					$result['internalPath'] = $result['path'];
					$result['path'] = $relativeMountPoint . $result['path'];
					$result['storage'] = $storage;
					$files[] = $result;
				}
			}
		}

		$result = array();
		foreach ($files as $file) {
			$result[] = $this->createNode($this->normalizePath($this->path . '/' . $file['path']), $file);
		}

		return $result;
	}

	/**
	 * @param int $id
	 * @return \OC\Files\Node\Node[]
	 */
	public function getById($id) {
		$mounts = $this->root->getMountsIn($this->path);
		$mounts[] = $this->root->getMount($this->path);
		// reverse the array so we start with the storage this view is in
		// which is the most likely to contain the file we're looking for
		$mounts = array_reverse($mounts);

		$nodes = array();
		foreach ($mounts as $mount) {
			/**
			 * @var \OC\Files\Mount\MountPoint $mount
			 */
			if ($mount->getStorage()) {
				$cache = $mount->getStorage()->getCache();
				$internalPath = $cache->getPathById($id);
				if (is_string($internalPath)) {
					$fullPath = $mount->getMountPoint() . $internalPath;
					if (!is_null($path = $this->getRelativePath($fullPath))) {
						$nodes[] = $this->get($path);
					}
				}
			}
		}
		return $nodes;
	}

	public function getFreeSpace() {
		return $this->view->free_space($this->path);
	}

	public function delete() {
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_DELETE)) {
			$this->sendHooks(array('preDelete'));
			$this->view->rmdir($this->path);
			$nonExisting = new NonExistingFolder($this->root, $this->view, $this->path);
			$this->root->emit('\OC\Files', 'postDelete', array($nonExisting));
			$this->exists = false;
		} else {
			throw new NotPermittedException();
		}
	}

	/**
	 * @param string $targetPath
	 * @throws \OCP\Files\NotPermittedException
	 * @return \OC\Files\Node\Node
	 */
	public function copy($targetPath) {
		$targetPath = $this->normalizePath($targetPath);
		$parent = $this->root->get(dirname($targetPath));
		if ($parent instanceof Folder and $this->isValidPath($targetPath) and $parent->isCreatable()) {
			$nonExisting = new NonExistingFolder($this->root, $this->view, $targetPath);
			$this->root->emit('\OC\Files', 'preCopy', array($this, $nonExisting));
			$this->root->emit('\OC\Files', 'preWrite', array($nonExisting));
			$this->view->copy($this->path, $targetPath);
			$targetNode = $this->root->get($targetPath);
			$this->root->emit('\OC\Files', 'postCopy', array($this, $targetNode));
			$this->root->emit('\OC\Files', 'postWrite', array($targetNode));
			return $targetNode;
		} else {
			throw new NotPermittedException();
		}
	}

	/**
	 * @param string $targetPath
	 * @throws \OCP\Files\NotPermittedException
	 * @return \OC\Files\Node\Node
	 */
	public function move($targetPath) {
		$targetPath = $this->normalizePath($targetPath);
		$parent = $this->root->get(dirname($targetPath));
		if ($parent instanceof Folder and $this->isValidPath($targetPath) and $parent->isCreatable()) {
			$nonExisting = new NonExistingFolder($this->root, $this->view, $targetPath);
			$this->root->emit('\OC\Files', 'preRename', array($this, $nonExisting));
			$this->root->emit('\OC\Files', 'preWrite', array($nonExisting));
			$this->view->rename($this->path, $targetPath);
			$targetNode = $this->root->get($targetPath);
			$this->root->emit('\OC\Files', 'postRename', array($this, $targetNode));
			$this->root->emit('\OC\Files', 'postWrite', array($targetNode));
			$this->path = $targetPath;
			return $targetNode;
		} else {
			throw new NotPermittedException();
		}
	}
}
