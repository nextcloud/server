<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Mount;

use \OC\Files\Filesystem;
use OC\Files\Storage\Loader;
use OC\Files\Storage\Storage;

class Mount {
	/**
	 * @var \OC\Files\Storage\Storage $storage
	 */
	private $storage = null;
	private $class;
	private $storageId;
	private $arguments = array();
	private $mountPoint;

	/**
	 * @var \OC\Files\Storage\Loader $loader
	 */
	private $loader;

	/**
	 * @param string|\OC\Files\Storage\Storage $storage
	 * @param string $mountpoint
	 * @param array $arguments (optional)\
	 * @param \OC\Files\Storage\Loader $loader
	 */
	public function __construct($storage, $mountpoint, $arguments = null, $loader = null) {
		if (is_null($arguments)) {
			$arguments = array();
		}
		if (is_null($loader)) {
			$this->loader = new Loader();
		} else {
			$this->loader = $loader;
		}

		$mountpoint = $this->formatPath($mountpoint);
		if ($storage instanceof Storage) {
			$this->class = get_class($storage);
			$this->storage = $this->loader->wrap($mountpoint, $storage);
		} else {
			// Update old classes to new namespace
			if (strpos($storage, 'OC_Filestorage_') !== false) {
				$storage = '\OC\Files\Storage\\' . substr($storage, 15);
			}
			$this->class = $storage;
			$this->arguments = $arguments;
		}
		$this->mountPoint = $mountpoint;
	}

	/**
	 * @return string
	 */
	public function getMountPoint() {
		return $this->mountPoint;
	}

	/**
	 * @param string $mountPoint new mount point
	 */
	public function setMountPoint($mountPoint) {
		$this->mountPoint = $mountPoint;
	}

	/**
	 * create the storage that is mounted
	 *
	 * @return \OC\Files\Storage\Storage
	 */
	private function createStorage() {
		if (class_exists($this->class)) {
			try {
				return $this->loader->load($this->mountPoint, $this->class, $this->arguments);
			} catch (\Exception $exception) {
				\OC_Log::write('core', $exception->getMessage(), \OC_Log::ERROR);
				return null;
			}
		} else {
			\OC_Log::write('core', 'storage backend ' . $this->class . ' not found', \OC_Log::ERROR);
			return null;
		}
	}

	/**
	 * @return \OC\Files\Storage\Storage
	 */
	public function getStorage() {
		if (is_null($this->storage)) {
			$this->storage = $this->createStorage();
		}
		return $this->storage;
	}

	/**
	 * @return string
	 */
	public function getStorageId() {
		if (!$this->storageId) {
			if (is_null($this->storage)) {
				$storage = $this->createStorage(); //FIXME: start using exceptions
				if (is_null($storage)) {
					return null;
				}
				$this->storage = $storage;
			}
			$this->storageId = $this->storage->getId();
			if (strlen($this->storageId) > 64) {
				$this->storageId = md5($this->storageId);
			}
		}
		return $this->storageId;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function getInternalPath($path) {
		if ($this->mountPoint === $path or $this->mountPoint . '/' === $path) {
			$internalPath = '';
		} else {
			$internalPath = substr($path, strlen($this->mountPoint));
		}
		return $internalPath;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	private function formatPath($path) {
		$path = Filesystem::normalizePath($path);
		if (strlen($path) > 1) {
			$path .= '/';
		}
		return $path;
	}

	/**
	 * @param callable $wrapper
	 */
	public function wrapStorage($wrapper) {
		$this->storage = $wrapper($this->mountPoint, $this->storage);
	}
}
