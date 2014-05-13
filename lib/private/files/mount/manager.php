<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Mount;

use \OC\Files\Filesystem;

class Manager {
	/**
	 * @var Mount[]
	 */
	private $mounts = array();

	/**
	 * @param Mount $mount
	 */
	public function addMount($mount) {
		$this->mounts[$mount->getMountPoint()] = $mount;
	}

	/**
	 * @param string $mountPoint
	 */
	public function removeMount($mountPoint) {
		unset($this->mounts[$mountPoint]);
	}

	/**
	 * Find the mount for $path
	 *
	 * @param string $path
	 * @return Mount
	 */
	public function find($path) {
		\OC_Util::setupFS();
		$path = $this->formatPath($path);
		if (isset($this->mounts[$path])) {
			return $this->mounts[$path];
		}

		\OC_Hook::emit('OC_Filesystem', 'get_mountpoint', array('path' => $path));
		$foundMountPoint = '';
		$mountPoints = array_keys($this->mounts);
		foreach ($mountPoints as $mountpoint) {
			if (strpos($path, $mountpoint) === 0 and strlen($mountpoint) > strlen($foundMountPoint)) {
				$foundMountPoint = $mountpoint;
			}
		}
		if (isset($this->mounts[$foundMountPoint])) {
			return $this->mounts[$foundMountPoint];
		} else {
			return null;
		}
	}

	/**
	 * Find all mounts in $path
	 *
	 * @param string $path
	 * @return Mount[]
	 */
	public function findIn($path) {
		\OC_Util::setupFS();
		$path = $this->formatPath($path);
		$result = array();
		$pathLength = strlen($path);
		$mountPoints = array_keys($this->mounts);
		foreach ($mountPoints as $mountPoint) {
			if (substr($mountPoint, 0, $pathLength) === $path and strlen($mountPoint) > $pathLength) {
				$result[] = $this->mounts[$mountPoint];
			}
		}
		return $result;
	}

	public function clear() {
		$this->mounts = array();
	}

	/**
	 * Find mounts by storage id
	 *
	 * @param string $id
	 * @return Mount[]
	 */
	public function findByStorageId($id) {
		\OC_Util::setupFS();
		if (strlen($id) > 64) {
			$id = md5($id);
		}
		$result = array();
		foreach ($this->mounts as $mount) {
			if ($mount->getStorageId() === $id) {
				$result[] = $mount;
			}
		}
		return $result;
	}

	/**
	 * @return Mount[]
	 */
	public function getAll() {
		return $this->mounts;
	}

	/**
	 * Find mounts by numeric storage id
	 *
	 * @param int $id
	 * @return Mount[]
	 */
	public function findByNumericId($id) {
		$storageId = \OC\Files\Cache\Storage::getStorageId($id);
		return $this->findByStorageId($storageId);
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
}
