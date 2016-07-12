<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Martin Mattel <martin.mattel@diemattels.at>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Owen Winkler <a_github@midnightcircus.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Files\Cache;

use OC\Files\Filesystem;
use OC\Hooks\BasicEmitter;
use OCP\Config;
use OCP\Lock\ILockingProvider;

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

	/**
	 * @var \OCP\Lock\ILockingProvider
	 */
	protected $lockingProvider;

	const SCAN_RECURSIVE = true;
	const SCAN_SHALLOW = false;

	const REUSE_ETAG = 1;
	const REUSE_SIZE = 2;

	public function __construct(\OC\Files\Storage\Storage $storage) {
		$this->storage = $storage;
		$this->storageId = $this->storage->getId();
		$this->cache = $storage->getCache();
		$this->cacheActive = !Config::getSystemValue('filesystem_cache_readonly', false);
		$this->lockingProvider = \OC::$server->getLockingProvider();
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
		$data = $this->storage->getMetaData($path);
		if (is_null($data)) {
			\OCP\Util::writeLog('OC\Files\Cache\Scanner', "!!! Path '$path' is not accessible or present !!!", \OCP\Util::DEBUG);
		}
		return $data;
	}

	/**
	 * scan a single file and store it in the cache
	 *
	 * @param string $file
	 * @param int $reuseExisting
	 * @param int $parentId
	 * @param array | null $cacheData existing data in the cache for the file to be scanned
	 * @param bool $lock set to false to disable getting an additional read lock during scanning
	 * @return array an array of metadata of the scanned file
	 * @throws \OC\ServerNotAvailableException
	 * @throws \OCP\Lock\LockedException
	 */
	public function scanFile($file, $reuseExisting = 0, $parentId = -1, $cacheData = null, $lock = true) {
		if (!self::isPartialFile($file)
			and !Filesystem::isFileBlacklisted($file)
		) {
			if ($lock) {
				$this->storage->acquireLock($file, ILockingProvider::LOCK_SHARED, $this->lockingProvider);
			}
			$this->emit('\OC\Files\Cache\Scanner', 'scanFile', array($file, $this->storageId));
			\OC_Hook::emit('\OC\Files\Cache\Scanner', 'scan_file', array('path' => $file, 'storage' => $this->storageId));
			$data = $this->getData($file);
			if ($data) {
				$parent = dirname($file);
				if ($parent === '.' or $parent === '/') {
					$parent = '';
				}
				if ($parentId === -1) {
					$parentId = $this->cache->getId($parent);
				}

				// scan the parent if it's not in the cache (id -1) and the current file is not the root folder
				if ($file and $parentId === -1) {
					$parentData = $this->scanFile($parent);
					$parentId = $parentData['fileid'];
				}
				if ($parent) {
					$data['parent'] = $parentId;
				}
				if (is_null($cacheData)) {
					$cacheData = $this->cache->get($file);
				}
				if ($cacheData and $reuseExisting and isset($cacheData['fileid'])) {
					// prevent empty etag
					if (empty($cacheData['etag'])) {
						$etag = $data['etag'];
					} else {
						$etag = $cacheData['etag'];
					}
					$fileId = $cacheData['fileid'];
					$data['fileid'] = $fileId;
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
					$fileId = -1;
				}
				if (!empty($newData)) {
					$data['fileid'] = $this->addToCache($file, $newData, $fileId);
				}
				$this->emit('\OC\Files\Cache\Scanner', 'postScanFile', array($file, $this->storageId));
				\OC_Hook::emit('\OC\Files\Cache\Scanner', 'post_scan_file', array('path' => $file, 'storage' => $this->storageId));
			} else {
				$this->removeFromCache($file);
			}
			if ($lock) {
				$this->storage->releaseLock($file, ILockingProvider::LOCK_SHARED, $this->lockingProvider);
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
	 * @param int $fileId
	 * @return int the id of the added file
	 */
	protected function addToCache($path, $data, $fileId = -1) {
		\OC_Hook::emit('Scanner', 'addToCache', array('file' => $path, 'data' => $data));
		$this->emit('\OC\Files\Cache\Scanner', 'addToCache', array($path, $this->storageId, $data));
		if ($this->cacheActive) {
			if ($fileId !== -1) {
				$this->cache->update($fileId, $data);
				return $fileId;
			} else {
				return $this->cache->put($path, $data);
			}
		} else {
			return -1;
		}
	}

	/**
	 * @param string $path
	 * @param array $data
	 * @param int $fileId
	 */
	protected function updateCache($path, $data, $fileId = -1) {
		\OC_Hook::emit('Scanner', 'addToCache', array('file' => $path, 'data' => $data));
		$this->emit('\OC\Files\Cache\Scanner', 'updateCache', array($path, $this->storageId, $data));
		if ($this->cacheActive) {
			if ($fileId !== -1) {
				$this->cache->update($fileId, $data);
			} else {
				$this->cache->put($path, $data);
			}
		}
	}

	/**
	 * scan a folder and all it's children
	 *
	 * @param string $path
	 * @param bool $recursive
	 * @param int $reuse
	 * @param bool $lock set to false to disable getting an additional read lock during scanning
	 * @return array an array of the meta data of the scanned file or folder
	 */
	public function scan($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1, $lock = true) {
		if ($reuse === -1) {
			$reuse = ($recursive === self::SCAN_SHALLOW) ? self::REUSE_ETAG | self::REUSE_SIZE : self::REUSE_ETAG;
		}
		if ($lock) {
			$this->lockingProvider->acquireLock('scanner::' . md5($this->storageId . '::' . $path), ILockingProvider::LOCK_EXCLUSIVE);
			$this->storage->acquireLock($path, ILockingProvider::LOCK_SHARED, $this->lockingProvider);
		}
		$data = $this->scanFile($path, $reuse, -1, null, $lock);
		if ($data and $data['mimetype'] === 'httpd/unix-directory') {
			$size = $this->scanChildren($path, $recursive, $reuse, $data, $lock);
			$data['size'] = $size;
		}
		if ($lock) {
			$this->storage->releaseLock($path, ILockingProvider::LOCK_SHARED, $this->lockingProvider);
			$this->lockingProvider->releaseLock('scanner::' . md5($this->storageId . '::' . $path), ILockingProvider::LOCK_EXCLUSIVE);
		}
		return $data;
	}

	/**
	 * Get the children currently in the cache
	 *
	 * @param int $folderId
	 * @return array[]
	 */
	protected function getExistingChildren($folderId) {
		$existingChildren = array();
		$children = $this->cache->getFolderContentsById($folderId);
		foreach ($children as $child) {
			$existingChildren[$child['name']] = $child;
		}
		return $existingChildren;
	}

	/**
	 * Get the children from the storage
	 *
	 * @param string $folder
	 * @return string[]
	 */
	protected function getNewChildren($folder) {
		$children = array();
		if ($dh = $this->storage->opendir($folder)) {
			if (is_resource($dh)) {
				while (($file = readdir($dh)) !== false) {
					if (!Filesystem::isIgnoredDir($file)) {
						$children[] = $file;
					}
				}
			}
		}
		return $children;
	}

	/**
	 * scan all the files and folders in a folder
	 *
	 * @param string $path
	 * @param bool $recursive
	 * @param int $reuse
	 * @param array $folderData existing cache data for the folder to be scanned
	 * @param bool $lock set to false to disable getting an additional read lock during scanning
	 * @return int the size of the scanned folder or -1 if the size is unknown at this stage
	 */
	protected function scanChildren($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1, $folderData = null, $lock = true) {
		if ($reuse === -1) {
			$reuse = ($recursive === self::SCAN_SHALLOW) ? self::REUSE_ETAG | self::REUSE_SIZE : self::REUSE_ETAG;
		}
		$this->emit('\OC\Files\Cache\Scanner', 'scanFolder', array($path, $this->storageId));
		$size = 0;
		$childQueue = array();
		if (is_array($folderData) and isset($folderData['fileid'])) {
			$folderId = $folderData['fileid'];
		} else {
			$folderId = $this->cache->getId($path);
		}
		$existingChildren = $this->getExistingChildren($folderId);
		$newChildren = $this->getNewChildren($path);

		if ($this->useTransactions) {
			\OC_DB::beginTransaction();
		}
		$exceptionOccurred = false;
		foreach ($newChildren as $file) {
			$child = ($path) ? $path . '/' . $file : $file;
			try {
				$existingData = isset($existingChildren[$file]) ? $existingChildren[$file] : null;
				$data = $this->scanFile($child, $reuse, $folderId, $existingData, $lock);
				if ($data) {
					if ($data['mimetype'] === 'httpd/unix-directory' and $recursive === self::SCAN_RECURSIVE) {
						$childQueue[$child] = $data;
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
				\OCP\Util::writeLog('core', 'Exception while scanning file "' . $child . '": ' . $ex->getMessage(), \OCP\Util::DEBUG);
				$exceptionOccurred = true;
			} catch (\OCP\Lock\LockedException $e) {
				if ($this->useTransactions) {
					\OC_DB::rollback();
				}
				throw $e;
			}
		}
		$removedChildren = \array_diff(array_keys($existingChildren), $newChildren);
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
			\OC::$server->getMimeTypeLoader()->reset();
		}

		foreach ($childQueue as $child => $childData) {
			$childSize = $this->scanChildren($child, self::SCAN_RECURSIVE, $reuse, $childData, $lock);
			if ($childSize === -1) {
				$size = -1;
			} else if ($size !== -1) {
				$size += $childSize;
			}
		}
		if (!is_array($folderData) or !isset($folderData['size']) or $folderData['size'] !== $size) {
			$this->updateCache($path, array('size' => $size), $folderId);
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
		if (strpos($file, '.part/') !== false) {
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
			try {
				$this->scan($path, self::SCAN_RECURSIVE, self::REUSE_ETAG);
				\OC_Hook::emit('Scanner', 'correctFolderSize', array('path' => $path));
				if ($this->cacheActive) {
					$this->cache->correctFolderSize($path);
				}
			} catch (\OCP\Files\StorageInvalidException $e) {
				// skip unavailable storages
			} catch (\OCP\Files\StorageNotAvailableException $e) {
				// skip unavailable storages
			} catch (\OCP\Lock\LockedException $e) {
				// skip unavailable storages
			}
			// FIXME: this won't proceed with the next item, needs revamping of getIncomplete()
			// to make this possible
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
