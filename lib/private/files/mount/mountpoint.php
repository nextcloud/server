<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Mount;

use \OC\Files\Filesystem;
use OC\Files\Storage\StorageFactory;
use OC\Files\Storage\Storage;
use OCP\Files\Mount\IMountPoint;

class MountPoint implements IMountPoint {
	/**
	 * @var \OC\Files\Storage\Storage $storage
	 */
	protected $storage = null;
	protected $class;
	protected $storageId;

	/**
	 * Configuration options for the storage backend
	 *
	 * @var array
	 */
	protected $arguments = array();
	protected $mountPoint;

	/**
	 * Mount specific options
	 *
	 * @var array
	 */
	protected $mountOptions = array();

	/**
	 * @var \OC\Files\Storage\StorageFactory $loader
	 */
	private $loader;

	/**
	 * Specified whether the storage is invalid after failing to
	 * instantiate it.
	 *
	 * @var bool
	 */
	private $invalidStorage = false;

	/**
	 * @param string|\OC\Files\Storage\Storage $storage
	 * @param string $mountpoint
	 * @param array $arguments (optional) configuration for the storage backend
	 * @param \OCP\Files\Storage\IStorageFactory $loader
	 * @param array $mountOptions mount specific options
	 */
	public function __construct($storage, $mountpoint, $arguments = null, $loader = null, $mountOptions = null) {
		if (is_null($arguments)) {
			$arguments = array();
		}
		if (is_null($loader)) {
			$this->loader = new StorageFactory();
		} else {
			$this->loader = $loader;
		}

		if (!is_null($mountOptions)) {
			$this->mountOptions = $mountOptions;
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
	 * get complete path to the mount point, relative to data/
	 *
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
		if ($this->invalidStorage) {
			return null;
		}

		if (class_exists($this->class)) {
			try {
				return $this->loader->getInstance($this->mountPoint, $this->class, $this->arguments);
			} catch (\Exception $exception) {
				$this->invalidStorage = true;
				if ($this->mountPoint === '/') {
					// the root storage could not be initialized, show the user!
					throw new \Exception('The root storage could not be initialized. Please contact your local administrator.', $exception->getCode(), $exception);
				} else {
					\OC_Log::write('core', $exception->getMessage(), \OC_Log::ERROR);
				}
				return null;
			}
		} else {
			\OC_Log::write('core', 'storage backend ' . $this->class . ' not found', \OC_Log::ERROR);
			$this->invalidStorage = true;
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
		// substr returns false instead of an empty string, we always want a string
		return (string)$internalPath;
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
		$storage = $this->getStorage();
		// storage can be null if it couldn't be initialized
		if ($storage != null) {
			$this->storage = $wrapper($this->mountPoint, $storage);
		}
	}

	/**
	 * Get a mount option
	 *
	 * @param string $name Name of the mount option to get
	 * @param mixed $default Default value for the mount option
	 * @return mixed
	 */
	public function getOption($name, $default) {
		return isset($this->mountOptions[$name]) ? $this->mountOptions[$name] : $default;
	}
}
