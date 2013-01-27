<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files;

class Mount {
	/**
	 * @var Mount[]
	 */
	static private $mounts = array();

	/**
	 * @var \OC\Files\Storage\Storage $storage
	 */
	private $storage = null;
	private $class;
	private $storageId;
	private $arguments = array();
	private $mountPoint;

	/**
	 * @param string|\OC\Files\Storage\Storage $storage
	 * @param string $mountpoint
	 * @param array $arguments (optional)
	 */
	public function __construct($storage, $mountpoint, $arguments = null) {
		if (is_null($arguments)) {
			$arguments = array();
		}

		$mountpoint = self::formatPath($mountpoint);
		if ($storage instanceof \OC\Files\Storage\Storage) {
			$this->class = get_class($storage);
			$this->storage = $storage;
		} else {
			// Update old classes to new namespace
			if (strpos($storage, 'OC_Filestorage_') !== false) {
				$storage = '\OC\Files\Storage\\' . substr($storage, 15);
			}
			$this->class = $storage;
			$this->arguments = $arguments;
		}
		$this->mountPoint = $mountpoint;

		self::$mounts[$this->mountPoint] = $this;
	}

	/**
	 * @return string
	 */
	public function getMountPoint() {
		return $this->mountPoint;
	}

	/**
	 * @return \OC\Files\Storage\Storage
	 */
	private function createStorage() {
		if (class_exists($this->class)) {
			try {
				return new $this->class($this->arguments);
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
				$this->storage = $this->createStorage();
			}
			$this->storageId = $this->storage->getId();
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
	private static function formatPath($path) {
		$path = Filesystem::normalizePath($path);
		if (strlen($path) > 1) {
			$path .= '/';
		}
		return $path;
	}

	/**
	 * Find the mount for $path
	 *
	 * @param $path
	 * @return Mount
	 */
	public static function find($path) {
		$path = self::formatPath($path);
		if (isset(self::$mounts[$path])) {
			return self::$mounts[$path];
		}

		\OC_Hook::emit('OC_Filesystem', 'get_mountpoint', array('path' => $path));
		$foundMountPoint = '';
		$mountPoints = array_keys(self::$mounts);
		foreach ($mountPoints as $mountpoint) {
			if (strpos($path, $mountpoint) === 0 and strlen($mountpoint) > strlen($foundMountPoint)) {
				$foundMountPoint = $mountpoint;
			}
		}
		if (isset(self::$mounts[$foundMountPoint])) {
			return self::$mounts[$foundMountPoint];
		} else {
			return null;
		}
	}

	/**
	 * Find all mounts in $path
	 *
	 * @param $path
	 * @return Mount[]
	 */
	public static function findIn($path) {
		$path = self::formatPath($path);
		$result = array();
		$pathLength = strlen($path);
		$mountPoints = array_keys(self::$mounts);
		foreach ($mountPoints as $mountPoint) {
			if (substr($mountPoint, 0, $pathLength) === $path and strlen($mountPoint) > $pathLength) {
				$result[] = self::$mounts[$mountPoint];
			}
		}
		return $result;
	}

	public static function clear() {
		self::$mounts = array();
	}

	/**
	 * @param string $id
	 * @return \OC\Files\Storage\Storage[]
	 */
	public static function findById($id) {
		$result = array();
		foreach (self::$mounts as $mount) {
			if ($mount->getStorageId() === $id) {
				$result[] = $mount;
			}
		}
		return $result;
	}
}
