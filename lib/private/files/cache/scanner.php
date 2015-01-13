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
use OCP\Config;

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
	protected $storage;

	/**
	 * @var string $storageId
	 */
	protected $storageId;

	/**
	 * @var \OC\Files\Cache\Cache $cache
	 */
	protected $cache;

	/**
	 * @var boolean $cacheActive If true, perform cache operations, if false, do not affect cache
	 */
	protected $cacheActive;

	/**
	 * @var bool $useTransactions whether to use transactions
	 */
	protected $useTransactions = true;

	const SCAN_RECURSIVE = true;
	const SCAN_SHALLOW = false;

	const REUSE_ETAG = 1;
	const REUSE_SIZE = 2;

	public function __construct(\OC\Files\Storage\Storage $storage) {
		$this->storage = $storage;
		$this->storageId = $this->storage->getId();
		$this->cache = $storage->getCache();
		$this->cacheActive = !Config::getSystemValue('filesystem_cache_readonly', false);
	}

	/**
	 * Whether to wrap the scanning of a folder in a database transaction
	 * On default transactions are used
	 *
	 * @param bool $useTransactions
	 */
	public function setUseTransactions($useTransactions) {
		$this->useTransactions = $useTransactions;
	}

	/**
	 * get all the metadata of a file or folder
	 * *
	 *
	 * @param string $path
	 * @return array an array of metadata of the file
	 */
	public function getData($path) {
		if (!$this->storage->isReadable($path)) {
			//cant read, nothing we can do
			\OCP\Util::writeLog('OC\Files\Cache\Scanner', "!!! Path '$path' is not accessible or present !!!", \OCP\Util::DEBUG);
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
		$data['permissions'] = $this->storage->getPermissions($path);
		return $data;
	}

	/**
	 * scan a single file and store it in the cache
	 *
	 * @param string $file
	 * @param int $reuseExisting
	 * @return array an array of metadata of the scanned file
	 */
	public function scanFile($file, $reuseExisting = 0) {
		if (!self::isPartialFile($file)
			and !Filesystem::isFileBlacklisted($file)
		) {
			$this->emit('\OC\Files\Cache\Scanner', 'scanFile', array($file, $this->storageId));
			\OC_Hook::emit('\OC\Files\Cache\Scanner', 'scan_file', array('path' => $file, 'storage' => $this->storageId));
			$data = $this->getData($file);
			if ($data) {
				$parent = dirname($file);
				if ($parent === '.' or $parent === '/') {
					$parent = '';
				}
				$parentId = $this->cache->getId($parent);

				// scan the parent if it's not in the cache (id -1) and the current file is not the root folder
				if ($file and $parentId === -1) {
					$parentData = $this->scanFile($parent);
					$parentId = $parentData['fileid'];
				}
				if ($parent) {
					$data['parent'] = $parentId;
				}
				$cacheData = $this->cache->get($file);
				if ($cacheData and $reuseExisting) {
					// prevent empty etag
					if (empty($cacheData['etag'])) {
						$etag = $data['etag'];
					} else {
						$etag = $cacheData['etag'];
					}
					// only reuse data if the file hasn't explicitly changed
					if (isset($data['storage_mtime']) && isset($cacheData['storage_mtime']) && $data['storage_mtime'] === $cacheData['storage_mtime']) {
						$data['mtime'] = $cacheData['mtime'];
						if (($reuseExisting & self::REUSE_SIZE) && ($data['size'] === -1)) {
							$data['size'] = $cacheData['size'];
						}
						if ($reuseExisting & self::REUSE_ETAG) {
							$data['etag'] = $etag;
						}
					}
					// Only update metadata that has changed
					$newData = array_diff_assoc($data, $cacheData);
				} else {
					$newData = $data;
				}
				if (!empty($newData)) {
					$data['fileid'] = $this->addToCache($file, $newData);
					$this->emit('\OC\Files\Cache\Scanner', 'postScanFile', array($file, $this->storageId));
					\OC_Hook::emit('\OC\Files\Cache\Scanner', 'post_scan_file', array('path' => $file, 'storage' => $this->storageId));
				}
			} else {
				$this->removeFromCache($file);
			}
			return $data;
		}
		return null;
	}

	protected function removeFromCache($path) {
		\OC_Hook::emit('Scanner', 'removeFromCache', array('file' => $path));
		$this->emit('\OC\Files\Cache\Scanner', 'removeFromCache', array($path));
		if ($this->cacheActive) {
			$this->cache->remove($path);
		}
	}

	/**
	 * @param string $path
	 * @param array $data
	 * @return int the id of the added file
	 */
	protected function addToCache($path, $data) {
		\OC_Hook::emit('Scanner', 'addToCache', array('file' => $path, 'data' => $data));
		$this->emit('\OC\Files\Cache\Scanner', 'addToCache', array($path, $this->storageId, $data));
		if ($this->cacheActive) {
			return $this->cache->put($path, $data);
		} else {
			return -1;
		}
	}

	/**
	 * @param string $path
	 * @param array $data
	 */
	protected function updateCache($path, $data) {
		\OC_Hook::emit('Scanner', 'addToCache', array('file' => $path, 'data' => $data));
		$this->emit('\OC\Files\Cache\Scanner', 'updateCache', array($path, $this->storageId, $data));
		if ($this->cacheActive) {
			$this->cache->put($path, $data);
		}
	}

	/**
	 * scan a folder and all it's children
	 *
	 * @param string $path
	 * @param bool $recursive
	 * @param int $reuse
	 * @return array an array of the meta data of the scanned file or folder
	 */
	public function scan($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1) {
		if ($reuse === -1) {
			$reuse = ($recursive === self::SCAN_SHALLOW) ? self::REUSE_ETAG | self::REUSE_SIZE : self::REUSE_ETAG;
		}
		$data = $this->scanFile($path, $reuse);
		if ($data !== null) {
			$size = $this->scanChildren($path, $recursive, $reuse);
			$data['size'] = $size;
		}
		return $data;
	}

	protected function getExistingChildren($path) {
		$existingChildren = array();
		if ($this->cache->inCache($path)) {
			$children = $this->cache->getFolderContents($path);
			foreach ($children as $child) {
				$existingChildren[] = $child['name'];
			}
		}
		return $existingChildren;
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
			$reuse = ($recursive === self::SCAN_SHALLOW) ? self::REUSE_ETAG | self::REUSE_SIZE : self::REUSE_ETAG;
		}
		$this->emit('\OC\Files\Cache\Scanner', 'scanFolder', array($path, $this->storageId));
		$size = 0;
		$childQueue = array();
		$existingChildren = $this->getExistingChildren($path);
		$newChildren = array();
		if ($this->storage->is_dir($path) && ($dh = $this->storage->opendir($path))) {
			$exceptionOccurred = false;
			if ($this->useTransactions) {
				\OC_DB::beginTransaction();
			}
			if (is_resource($dh)) {
				while (($file = readdir($dh)) !== false) {
					$child = ($path) ? $path . '/' . $file : $file;
					if (!Filesystem::isIgnoredDir($file)) {
						$newChildren[] = $file;
						try {
							$data = $this->scanFile($child, $reuse);
							if ($data) {
								if ($data['mimetype'] === 'httpd/unix-directory' and $recursive === self::SCAN_RECURSIVE) {
									$childQueue[] = $child;
								} else if ($data['size'] === -1) {
									$size = -1;
								} else if ($size !== -1) {
									$size += $data['size'];
								}
							}
						} catch (\Doctrine\DBAL\DBALException $ex) {
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
				$this->removeFromCache($child);
			}
			if ($this->useTransactions) {
				\OC_DB::commit();
			}
			if ($exceptionOccurred) {
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
				} else if ($size !== -1) {
					$size += $childSize;
				}
			}
			$this->updateCache($path, array('size' => $size));
		}
		$this->emit('\OC\Files\Cache\Scanner', 'postScanFolder', array($path, $this->storageId));
		return $size;
	}

	/**
	 * check if the file should be ignored when scanning
	 * NOTE: files with a '.part' extension are ignored as well!
	 *       prevents unfinished put requests to be scanned
	 *
	 * @param string $file
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
			$this->scan($path, self::SCAN_RECURSIVE, self::REUSE_ETAG);
			\OC_Hook::emit('Scanner', 'correctFolderSize', array('path' => $path));
			if ($this->cacheActive) {
				$this->cache->correctFolderSize($path);
			}
			$lastPath = $path;
		}
	}

	/**
	 * Set whether the cache is affected by scan operations
	 *
	 * @param boolean $active The active state of the cache
	 */
	public function setCacheActive($active) {
		$this->cacheActive = $active;
	}
}
