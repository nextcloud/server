<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

use OC\Files\Filesystem;
use OC\Hooks\BasicEmitter;

/**
 * Class Scanner
 *
 * Hooks available in scope \OC\Files\Cache\Scanner:
 *  - scanFile(string $path, string $storageId)
 *  - scanFolder(string $path, string $storageId)
 *  - postScanFile(string $path, string $storageId)
 *  - postScanFolder(string $path, string $storageId)
 *
 * @package OC\Files\Cache
 */
class Scanner extends BasicEmitter {
	/**
	 * @var \OC\Files\Storage\Storage $storage
	 */
	private $storage;

	/**
	 * @var string $storageId
	 */
	private $storageId;

	/**
	 * @var \OC\Files\Cache\Cache $cache
	 */
	private $cache;

	/**
	 * @var \OC\Files\Cache\Permissions $permissionsCache
	 */
	private $permissionsCache;

	const SCAN_RECURSIVE = true;
	const SCAN_SHALLOW = false;

	const REUSE_ETAG = 1;
	const REUSE_SIZE = 2;

	public function __construct(\OC\Files\Storage\Storage $storage) {
		$this->storage = $storage;
		$this->storageId = $this->storage->getId();
		$this->cache = $storage->getCache();
		$this->permissionsCache = $storage->getPermissionsCache();
	}

	/**
	 * get all the metadata of a file or folder
	 * *
	 *
	 * @param string $path
	 * @return array with metadata of the file
	 */
	public function getData($path) {
		if (!$this->storage->isReadable($path)) {
			//cant read, nothing we can do
			\OCP\Util::writeLog('OC\Files\Cache\Scanner', "!!! Path '$path' is not readable !!!", \OCP\Util::DEBUG);
			return null;
		}
		$data = array();
		$data['mimetype'] = $this->storage->getMimeType($path);
		$data['mtime'] = $this->storage->filemtime($path);
		if ($data['mimetype'] == 'httpd/unix-directory') {
			$data['size'] = -1; //unknown
		} else {
			$data['size'] = $this->storage->filesize($path);
		}
		$data['etag'] = $this->storage->getETag($path);
		$data['storage_mtime'] = $data['mtime'];
		return $data;
	}

	/**
	 * scan a single file and store it in the cache
	 *
	 * @param string $file
	 * @param int $reuseExisting
	 * @param bool $parentExistsInCache
	 * @return array with metadata of the scanned file
	 */
	public function scanFile($file, $reuseExisting = 0, $parentExistsInCache = false) {
		if (!self::isPartialFile($file)
			and !Filesystem::isFileBlacklisted($file)
		) {
			$this->emit('\OC\Files\Cache\Scanner', 'scanFile', array($file, $this->storageId));
			\OC_Hook::emit('\OC\Files\Cache\Scanner', 'scan_file', array('path' => $file, 'storage' => $this->storageId));
			$data = $this->getData($file);
			if ($data) {
				if ($file and !$parentExistsInCache) {
					$parent = dirname($file);
					if ($parent === '.' or $parent === '/') {
						$parent = '';
					}
					if (!$this->cache->inCache($parent)) {
						$this->scanFile($parent);
					}
				}
				$newData = $data;
				$cacheData = $this->cache->get($file);
				if ($cacheData) {
					if (isset($cacheData['fileid'])) {
						$this->permissionsCache->remove($cacheData['fileid']);
					}
					if ($reuseExisting) {
						// prevent empty etag
						$etag = $cacheData['etag'];
						$propagateETagChange = false;
						if (empty($etag)) {
							$etag = $data['etag'];
							$propagateETagChange = true;
						}
						// only reuse data if the file hasn't explicitly changed
						if (isset($data['mtime']) && isset($cacheData['mtime']) && $data['mtime'] === $cacheData['mtime']) {
							if (($reuseExisting & self::REUSE_SIZE) && ($data['size'] === -1)) {
								$data['size'] = $cacheData['size'];
							}
							if ($reuseExisting & self::REUSE_ETAG) {
								$data['etag'] = $etag;
								if ($propagateETagChange) {
									$parent = $file;
									while ($parent !== '') {
										$parent = dirname($parent);
										if ($parent === '.') {
											$parent = '';
										}
										$parentCacheData = $this->cache->get($parent);
										$this->cache->update($parentCacheData['fileid'], array(
											'etag' => $this->storage->getETag($parent),
										));
									}
								}
							}
						}
						// Only update metadata that has changed
						$newData = array_diff_assoc($data, $cacheData);
						if (isset($newData['etag'])) {
							$cacheDataString = print_r($cacheData, true);
							$dataString = print_r($data, true);
							\OCP\Util::writeLog('OC\Files\Cache\Scanner',
								"!!! No reuse of etag for '$file' !!! \ncache: $cacheDataString \ndata: $dataString",
								\OCP\Util::DEBUG);
						}
					}
				}
				if (!empty($newData)) {
					$this->cache->put($file, $newData);
					$this->emit('\OC\Files\Cache\Scanner', 'postScanFile', array($file, $this->storageId));
					\OC_Hook::emit('\OC\Files\Cache\Scanner', 'post_scan_file', array('path' => $file, 'storage' => $this->storageId));
				}
			} else {
				$this->cache->remove($file);
			}
			return $data;
		}
		return null;
	}

	/**
	 * scan a folder and all it's children
	 *
	 * @param string $path
	 * @param bool $recursive
	 * @param int $reuse
	 * @return int the size of the scanned folder or -1 if the size is unknown at this stage
	 */
	public function scan($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1) {
		if ($reuse === -1) {
			$reuse = ($recursive === self::SCAN_SHALLOW) ? self::REUSE_ETAG | self::REUSE_SIZE : 0;
		}
		$this->scanFile($path, $reuse);
		return $this->scanChildren($path, $recursive, $reuse);
	}

	/**
	 * scan all the files and folders in a folder
	 *
	 * @param string $path
	 * @param bool $recursive
	 * @param int $reuse
	 * @return int the size of the scanned folder or -1 if the size is unknown at this stage
	 */
	public function scanChildren($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1) {
		if ($reuse === -1) {
			$reuse = ($recursive === self::SCAN_SHALLOW) ? self::REUSE_ETAG | self::REUSE_SIZE : 0;
		}
		$this->emit('\OC\Files\Cache\Scanner', 'scanFolder', array($path, $this->storageId));
		$size = 0;
		$childQueue = array();
		$existingChildren = array();
		if ($this->cache->inCache($path)) {
			$children = $this->cache->getFolderContents($path);
			foreach ($children as $child) {
				$existingChildren[] = $child['name'];
			}
		}
		$newChildren = array();
		if ($this->storage->is_dir($path) && ($dh = $this->storage->opendir($path))) {
			$exceptionOccurred = false;
			\OC_DB::beginTransaction();
			if (is_resource($dh)) {
				while (($file = readdir($dh)) !== false) {
					$child = ($path) ? $path . '/' . $file : $file;
					if (!Filesystem::isIgnoredDir($file)) {
						$newChildren[] = $file;
						try {
							$data = $this->scanFile($child, $reuse, true);
							if ($data) {
								if ($data['size'] === -1) {
									if ($recursive === self::SCAN_RECURSIVE) {
										$childQueue[] = $child;
									} else {
										$size = -1;
									}
								} else if ($size !== -1) {
									$size += $data['size'];
								}
							}
						}
						catch (\Doctrine\DBAL\DBALException $ex){
							// might happen if inserting duplicate while a scanning
							// process is running in parallel
							// log and ignore
							\OC_Log::write('core', 'Exception while scanning file "' . $child . '": ' . $ex->getMessage(), \OC_Log::DEBUG);
							$exceptionOccurred = true;
						}
					}
				}
			}
			$removedChildren = \array_diff($existingChildren, $newChildren);
			foreach ($removedChildren as $childName) {
				$child = ($path) ? $path . '/' . $childName : $childName;
				$this->cache->remove($child);
			}
			\OC_DB::commit();
			if ($exceptionOccurred){
				// It might happen that the parallel scan process has already
				// inserted mimetypes but those weren't available yet inside the transaction
				// To make sure to have the updated mime types in such cases,
				// we reload them here
				$this->cache->loadMimetypes();
			}

			foreach ($childQueue as $child) {
				$childSize = $this->scanChildren($child, self::SCAN_RECURSIVE, $reuse);
				if ($childSize === -1) {
					$size = -1;
				} else {
					$size += $childSize;
				}
			}
			$this->cache->put($path, array('size' => $size));
		}
		$this->emit('\OC\Files\Cache\Scanner', 'postScanFolder', array($path, $this->storageId));
		return $size;
	}

	/**
	 * @brief check if the file should be ignored when scanning
	 * NOTE: files with a '.part' extension are ignored as well!
	 *       prevents unfinished put requests to be scanned
	 * @param String $file
	 * @return boolean
	 */
	public static function isPartialFile($file) {
		if (pathinfo($file, PATHINFO_EXTENSION) === 'part') {
			return true;
		}
		return false;
	}

	/**
	 * walk over any folders that are not fully scanned yet and scan them
	 */
	public function backgroundScan() {
		$lastPath = null;
		while (($path = $this->cache->getIncomplete()) !== false && $path !== $lastPath) {
			$this->scan($path);
			$this->cache->correctFolderSize($path);
			$lastPath = $path;
		}
	}
}
