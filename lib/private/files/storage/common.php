<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

/**
 * Storage backend class for providing common filesystem operation methods
 * which are not storage-backend specific.
 *
 * \OC\Files\Storage\Common is never used directly; it is extended by all other
 * storage backends, where its methods may be overridden, and additional
 * (backend-specific) methods are defined.
 *
 * Some \OC\Files\Storage\Common methods call functions which are first defined
 * in classes which extend it, e.g. $this->stat() .
 */

abstract class Common implements \OC\Files\Storage\Storage {
	protected $cache;
	protected $scanner;
	protected $permissioncache;
	protected $watcher;
	protected $storageCache;

	/**
	 * @var string[]
	 */
	protected $cachedFiles = array();

	public function __construct($parameters) {
	}

	public function is_dir($path) {
		return $this->filetype($path) == 'dir';
	}

	public function is_file($path) {
		return $this->filetype($path) == 'file';
	}

	public function filesize($path) {
		if ($this->is_dir($path)) {
			return 0; //by definition
		} else {
			$stat = $this->stat($path);
			if (isset($stat['size'])) {
				return $stat['size'];
			} else {
				return 0;
			}
		}
	}

	public function isReadable($path) {
		// at least check whether it exists
		// subclasses might want to implement this more thoroughly
		return $this->file_exists($path);
	}

	public function isUpdatable($path) {
		// at least check whether it exists
		// subclasses might want to implement this more thoroughly
		// a non-existing file/folder isn't updatable
		return $this->file_exists($path);
	}

	public function isCreatable($path) {
		if ($this->is_dir($path) && $this->isUpdatable($path)) {
			return true;
		}
		return false;
	}

	public function isDeletable($path) {
		return $this->isUpdatable($path);
	}

	public function isSharable($path) {
		return $this->isReadable($path);
	}

	public function getPermissions($path) {
		$permissions = 0;
		if ($this->isCreatable($path)) {
			$permissions |= \OCP\PERMISSION_CREATE;
		}
		if ($this->isReadable($path)) {
			$permissions |= \OCP\PERMISSION_READ;
		}
		if ($this->isUpdatable($path)) {
			$permissions |= \OCP\PERMISSION_UPDATE;
		}
		if ($this->isDeletable($path)) {
			$permissions |= \OCP\PERMISSION_DELETE;
		}
		if ($this->isSharable($path)) {
			$permissions |= \OCP\PERMISSION_SHARE;
		}
		return $permissions;
	}

	public function filemtime($path) {
		$stat = $this->stat($path);
		if (isset($stat['mtime'])) {
			return $stat['mtime'];
		} else {
			return 0;
		}
	}

	public function file_get_contents($path) {
		$handle = $this->fopen($path, "r");
		if (!$handle) {
			return false;
		}
		$data = stream_get_contents($handle);
		fclose($handle);
		return $data;
	}

	public function file_put_contents($path, $data) {
		$handle = $this->fopen($path, "w");
		$this->removeCachedFile($path);
		$count = fwrite($handle, $data);
		fclose($handle);
		return $count;
	}

	public function rename($path1, $path2) {
		if ($this->copy($path1, $path2)) {
			$this->removeCachedFile($path1);
			return $this->unlink($path1);
		} else {
			return false;
		}
	}

	public function copy($path1, $path2) {
		$source = $this->fopen($path1, 'r');
		$target = $this->fopen($path2, 'w');
		list($count, $result) = \OC_Helper::streamCopy($source, $target);
		$this->removeCachedFile($path2);
		return $result;
	}

	public function getMimeType($path) {
		if ($this->is_dir($path)) {
			return 'httpd/unix-directory';
		} elseif ($this->file_exists($path)) {
			return \OC_Helper::getFileNameMimeType($path);
		} else {
			return false;
		}
	}

	public function hash($type, $path, $raw = false) {
		$fh = $this->fopen($path, 'rb');
		$ctx = hash_init($type);
		hash_update_stream($ctx, $fh);
		fclose($fh);
		return hash_final($ctx, $raw);
	}

	public function search($query) {
		return $this->searchInDir($query);
	}

	public function getLocalFile($path) {
		return $this->getCachedFile($path);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function toTmpFile($path) { //no longer in the storage api, still useful here
		$source = $this->fopen($path, 'r');
		if (!$source) {
			return false;
		}
		if ($pos = strrpos($path, '.')) {
			$extension = substr($path, $pos);
		} else {
			$extension = '';
		}
		$tmpFile = \OC_Helper::tmpFile($extension);
		$target = fopen($tmpFile, 'w');
		\OC_Helper::streamCopy($source, $target);
		return $tmpFile;
	}

	public function getLocalFolder($path) {
		$baseDir = \OC_Helper::tmpFolder();
		$this->addLocalFolder($path, $baseDir);
		return $baseDir;
	}

	/**
	 * @param string $path
	 * @param string $target
	 */
	private function addLocalFolder($path, $target) {
		$dh = $this->opendir($path);
		if (is_resource($dh)) {
			while (($file = readdir($dh)) !== false) {
				if ($file !== '.' and $file !== '..') {
					if ($this->is_dir($path . '/' . $file)) {
						mkdir($target . '/' . $file);
						$this->addLocalFolder($path . '/' . $file, $target . '/' . $file);
					} else {
						$tmp = $this->toTmpFile($path . '/' . $file);
						rename($tmp, $target . '/' . $file);
					}
				}
			}
		}
	}

	/**
	 * @param string $query
	 */
	protected function searchInDir($query, $dir = '') {
		$files = array();
		$dh = $this->opendir($dir);
		if (is_resource($dh)) {
			while (($item = readdir($dh)) !== false) {
				if ($item == '.' || $item == '..') continue;
				if (strstr(strtolower($item), strtolower($query)) !== false) {
					$files[] = $dir . '/' . $item;
				}
				if ($this->is_dir($dir . '/' . $item)) {
					$files = array_merge($files, $this->searchInDir($query, $dir . '/' . $item));
				}
			}
		}
		return $files;
	}

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @param string $path
	 * @param int $time
	 * @return bool
	 */
	public function hasUpdated($path, $time) {
		return $this->filemtime($path) > $time;
	}

	public function getCache($path = '') {
		if (!isset($this->cache)) {
			$this->cache = new \OC\Files\Cache\Cache($this);
		}
		return $this->cache;
	}

	public function getScanner($path = '') {
		if (!isset($this->scanner)) {
			$this->scanner = new \OC\Files\Cache\Scanner($this);
		}
		return $this->scanner;
	}

	public function getPermissionsCache($path = '') {
		if (!isset($this->permissioncache)) {
			$this->permissioncache = new \OC\Files\Cache\Permissions($this);
		}
		return $this->permissioncache;
	}

	public function getWatcher($path = '') {
		if (!isset($this->watcher)) {
			$this->watcher = new \OC\Files\Cache\Watcher($this);
		}
		return $this->watcher;
	}

	public function getStorageCache() {
		if (!isset($this->storageCache)) {
			$this->storageCache = new \OC\Files\Cache\Storage($this);
		}
		return $this->storageCache;
	}

	/**
	 * get the owner of a path
	 *
	 * @param string $path The path to get the owner
	 * @return string uid or false
	 */
	public function getOwner($path) {
		return \OC_User::getUser();
	}

	/**
	 * get the ETag for a file or folder
	 *
	 * @param string $path
	 * @return string
	 */
	public function getETag($path) {
		$ETagFunction = \OC_Connector_Sabre_Node::$ETagFunction;
		if ($ETagFunction) {
			$hash = call_user_func($ETagFunction, $path);
			return $hash;
		} else {
			return uniqid();
		}
	}

	/**
	 * clean a path, i.e. remove all redundant '.' and '..'
	 * making sure that it can't point to higher than '/'
	 *
	 * @param $path The path to clean
	 * @return string cleaned path
	 */
	public function cleanPath($path) {
		if (strlen($path) == 0 or $path[0] != '/') {
			$path = '/' . $path;
		}

		$output = array();
		foreach (explode('/', $path) as $chunk) {
			if ($chunk == '..') {
				array_pop($output);
			} else if ($chunk == '.') {
			} else {
				$output[] = $chunk;
			}
		}
		return implode('/', $output);
	}

	public function test() {
		if ($this->stat('')) {
			return true;
		}
		return false;
	}

	/**
	 * get the free space in the storage
	 *
	 * @param $path
	 * @return int
	 */
	public function free_space($path) {
		return \OC\Files\SPACE_UNKNOWN;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isLocal() {
		// the common implementation returns a temporary file by
		// default, which is not local
		return false;
	}

	/**
	 * @param string $path
	 */
	protected function getCachedFile($path) {
		if (!isset($this->cachedFiles[$path])) {
			$this->cachedFiles[$path] = $this->toTmpFile($path);
		}
		return $this->cachedFiles[$path];
	}

	protected function removeCachedFile($path) {
		unset($this->cachedFiles[$path]);
	}
}
