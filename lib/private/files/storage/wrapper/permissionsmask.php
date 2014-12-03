<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage\Wrapper;

use OC\Files\Cache\Wrapper\CachePermissionsMask;
use OCP\Constants;

/**
 * Mask the permissions of a storage
 *
 * This can be used to restrict update, create, delete and/or share permissions of a storage
 *
 * Note that the read permissions cant be masked
 */
class PermissionsMask extends Wrapper {
	/**
	 * @var int the permissions bits we want to keep
	 */
	private $mask;

	/**
	 * @param array $arguments ['storage' => $storage, 'mask' => $mask]
	 *
	 * $storage: The storage the permissions mask should be applied on
	 * $mask: The permission bits that should be kept, a combination of the \OCP\Constant::PERMISSION_ constants
	 */
	public function __construct($arguments) {
		parent::__construct($arguments);
		$this->mask = $arguments['mask'];
	}

	private function checkMask($permissions) {
		return ($this->mask & $permissions) === $permissions;
	}

	public function isUpdatable($path) {
		return $this->checkMask(Constants::PERMISSION_UPDATE) and parent::isUpdatable($path);
	}

	public function isCreatable($path) {
		return $this->checkMask(Constants::PERMISSION_CREATE) and parent::isCreatable($path);
	}

	public function isDeletable($path) {
		return $this->checkMask(Constants::PERMISSION_DELETE) and parent::isDeletable($path);
	}

	public function getPermissions($path) {
		return $this->storage->getPermissions($path) & $this->mask;
	}

	public function rename($path1, $path2) {
		return $this->checkMask(Constants::PERMISSION_UPDATE) and parent::rename($path1, $path2);
	}

	public function copy($path1, $path2) {
		return $this->checkMask(Constants::PERMISSION_CREATE) and parent::copy($path1, $path2);
	}

	public function touch($path, $mtime = null) {
		$permissions = $this->file_exists($path) ? Constants::PERMISSION_UPDATE : Constants::PERMISSION_CREATE;
		return $this->checkMask($permissions) and parent::touch($path, $mtime);
	}

	public function mkdir($path) {
		return $this->checkMask(Constants::PERMISSION_CREATE) and parent::mkdir($path);
	}

	public function rmdir($path) {
		return $this->checkMask(Constants::PERMISSION_DELETE) and parent::rmdir($path);
	}

	public function unlink($path) {
		return $this->checkMask(Constants::PERMISSION_DELETE) and parent::unlink($path);
	}

	public function file_put_contents($path, $data) {
		$permissions = $this->file_exists($path) ? Constants::PERMISSION_UPDATE : Constants::PERMISSION_CREATE;
		return $this->checkMask($permissions) and parent::file_put_contents($path, $data);
	}

	public function fopen($path, $mode) {
		if ($mode === 'r' or $mode === 'rb') {
			return parent::fopen($path, $mode);
		} else {
			$permissions = $this->file_exists($path) ? Constants::PERMISSION_UPDATE : Constants::PERMISSION_CREATE;
			return $this->checkMask($permissions) ? parent::fopen($path, $mode) : false;
		}
	}

	/**
	 * get a cache instance for the storage
	 *
	 * @param string $path
	 * @param \OC\Files\Storage\Storage (optional) the storage to pass to the cache
	 * @return \OC\Files\Cache\Cache
	 */
	public function getCache($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		$sourceCache = parent::getCache($path, $storage);
		return new CachePermissionsMask($sourceCache, $this->mask);
	}
}
