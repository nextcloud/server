<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Ari Selseng <ari@selseng.net>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Jagszent <daniel@jagszent.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Martin Mattel <martin.mattel@diemattels.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Owen Winkler <a_github@midnightcircus.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Files\Cache;

use Doctrine\DBAL\Exception;
use OC\Files\Storage\Wrapper\Encryption;
use OCP\Files\Cache\IScanner;
use OCP\Files\ForbiddenException;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IReliableEtagStorage;
use OCP\IDBConnection;
use OCP\Lock\ILockingProvider;
use OC\Files\Storage\Wrapper\Jail;
use OC\Hooks\BasicEmitter;
use Psr\Log\LoggerInterface;

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
class Scanner extends BasicEmitter implements IScanner {
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

	protected IDBConnection $connection;

	public function __construct(\OC\Files\Storage\Storage $storage) {
		$this->storage = $storage;
		$this->storageId = $this->storage->getId();
		$this->cache = $storage->getCache();
		$this->cacheActive = !\OC::$server->getConfig()->getSystemValueBool('filesystem_cache_readonly', false);
		$this->lockingProvider = \OC::$server->getLockingProvider();
		$this->connection = \OC::$server->get(IDBConnection::class);
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
	 * @return array|null an array of metadata of the file
	 */
	protected function getData($path) {
		$data = $this->storage->getMetaData($path);
		if (is_null($data)) {
			\OC::$server->get(LoggerInterface::class)->debug("!!! Path '$path' is not accessible or present !!!", ['app' => 'core']);
		}
		return $data;
	}

	/**
	 * scan a single file and store it in the cache
	 *
	 * @param string $file
	 * @param int $reuseExisting
	 * @param int $parentId
	 * @param array|null|false $cacheData existing data in the cache for the file to be scanned
	 * @param bool $lock set to false to disable getting an additional read lock during scanning
	 * @param null $data the metadata for the file, as returned by the storage
	 * @return array|null an array of metadata of the scanned file
	 * @throws \OCP\Lock\LockedException
	 */
	public function scanFile($file, $reuseExisting = 0, $parentId = -1, $cacheData = null, $lock = true, $data = null) {
		if ($file !== '') {
			try {
				$this->storage->verifyPath(dirname($file), basename($file));
			} catch (\Exception $e) {
				return null;
			}
		}
		// only proceed if $file is not a partial file, blacklist is handled by the storage
		if (!self::isPartialFile($file)) {
			// acquire a lock
			if ($lock) {
				if ($this->storage->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
					$this->storage->acquireLock($file, ILockingProvider::LOCK_SHARED, $this->lockingProvider);
				}
			}

			try {
				$data = $data ?? $this->getData($file);
			} catch (ForbiddenException $e) {
				if ($lock) {
					if ($this->storage->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
						$this->storage->releaseLock($file, ILockingProvider::LOCK_SHARED, $this->lockingProvider);
					}
				}

				return null;
			}

			try {
				if ($data) {
					// pre-emit only if it was a file. By that we avoid counting/treating folders as files
					if ($data['mimetype'] !== 'httpd/unix-directory') {
						$this->emit('\OC\Files\Cache\Scanner', 'scanFile', [$file, $this->storageId]);
						\OC_Hook::emit('\OC\Files\Cache\Scanner', 'scan_file', ['path' => $file, 'storage' => $this->storageId]);
					}

					$parent = dirname($file);
					if ($parent === '.' || $parent === '/') {
						$parent = '';
					}
					if ($parentId === -1) {
						$parentId = $this->cache->getParentId($file);
					}

					// scan the parent if it's not in the cache (id -1) and the current file is not the root folder
					if ($file && $parentId === -1) {
						$parentData = $this->scanFile($parent);
						if (!$parentData) {
							return null;
						}
						$parentId = $parentData['fileid'];
					}
					if ($parent) {
						$data['parent'] = $parentId;
					}
					if (is_null($cacheData)) {
						/** @var CacheEntry $cacheData */
						$cacheData = $this->cache->get($file);
					}
					if ($cacheData && $reuseExisting && isset($cacheData['fileid'])) {
						// prevent empty etag
						$etag = empty($cacheData['etag']) ? $data['etag'] : $cacheData['etag'];
						$fileId = $cacheData['fileid'];
						$data['fileid'] = $fileId;
						// only reuse data if the file hasn't explicitly changed
						if (isset($data['storage_mtime']) && isset($cacheData['storage_mtime']) && $data['storage_mtime'] === $cacheData['storage_mtime']) {
							$data['mtime'] = $cacheData['mtime'];
							if (($reuseExisting & self::REUSE_SIZE) && ($data['size'] === -1)) {
								$data['size'] = $cacheData['size'];
							}
							if ($reuseExisting & self::REUSE_ETAG && !$this->storage->instanceOfStorage(IReliableEtagStorage::class)) {
								$data['etag'] = $etag;
							}
						}

						// we only updated unencrypted_size if it's already set
						if ($cacheData['unencrypted_size'] === 0) {
							unset($data['unencrypted_size']);
						}

						// Only update metadata that has changed
						$newData = array_diff_assoc($data, $cacheData->getData());
					} else {
						// we only updated unencrypted_size if it's already set
						unset($data['unencrypted_size']);
						$newData = $data;
						$fileId = -1;
					}
					if (!empty($newData)) {
						// Reset the checksum if the data has changed
						$newData['checksum'] = '';
						$newData['parent'] = $parentId;
						$data['fileid'] = $this->addToCache($file, $newData, $fileId);
					}

					$data['oldSize'] = ($cacheData && isset($cacheData['size'])) ? $cacheData['size'] : 0;

					if ($cacheData && isset($cacheData['encrypted'])) {
						$data['encrypted'] = $cacheData['encrypted'];
					}

					// post-emit only if it was a file. By that we avoid counting/treating folders as files
					if ($data['mimetype'] !== 'httpd/unix-directory') {
						$this->emit('\OC\Files\Cache\Scanner', 'postScanFile', [$file, $this->storageId]);
						\OC_Hook::emit('\OC\Files\Cache\Scanner', 'post_scan_file', ['path' => $file, 'storage' => $this->storageId]);
					}
				} else {
					$this->removeFromCache($file);
				}
			} catch (\Exception $e) {
				if ($lock) {
					if ($this->storage->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
						$this->storage->releaseLock($file, ILockingProvider::LOCK_SHARED, $this->lockingProvider);
					}
				}
				throw $e;
			}

			// release the acquired lock
			if ($lock) {
				if ($this->storage->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
					$this->storage->releaseLock($file, ILockingProvider::LOCK_SHARED, $this->lockingProvider);
				}
			}

			if ($data && !isset($data['encrypted'])) {
				$data['encrypted'] = false;
			}
			return $data;
		}

		return null;
	}

	protected function removeFromCache($path) {
		\OC_Hook::emit('Scanner', 'removeFromCache', ['file' => $path]);
		$this->emit('\OC\Files\Cache\Scanner', 'removeFromCache', [$path]);
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
		if (isset($data['scan_permissions'])) {
			$data['permissions'] = $data['scan_permissions'];
		}
		\OC_Hook::emit('Scanner', 'addToCache', ['file' => $path, 'data' => $data]);
		$this->emit('\OC\Files\Cache\Scanner', 'addToCache', [$path, $this->storageId, $data, $fileId]);
		if ($this->cacheActive) {
			if ($fileId !== -1) {
				$this->cache->update($fileId, $data);
				return $fileId;
			} else {
				return $this->cache->insert($path, $data);
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
		\OC_Hook::emit('Scanner', 'addToCache', ['file' => $path, 'data' => $data]);
		$this->emit('\OC\Files\Cache\Scanner', 'updateCache', [$path, $this->storageId, $data]);
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
	 * @return array|null an array of the meta data of the scanned file or folder
	 */
	public function scan($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1, $lock = true) {
		if ($reuse === -1) {
			$reuse = ($recursive === self::SCAN_SHALLOW) ? self::REUSE_ETAG | self::REUSE_SIZE : self::REUSE_ETAG;
		}
		if ($lock) {
			if ($this->storage->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
				$this->storage->acquireLock('scanner::' . $path, ILockingProvider::LOCK_EXCLUSIVE, $this->lockingProvider);
				$this->storage->acquireLock($path, ILockingProvider::LOCK_SHARED, $this->lockingProvider);
			}
		}
		try {
			try {
				$data = $this->scanFile($path, $reuse, -1, null, $lock);
				if ($data && $data['mimetype'] === 'httpd/unix-directory') {
					$size = $this->scanChildren($path, $recursive, $reuse, $data['fileid'], $lock, $data['size']);
					$data['size'] = $size;
				}
			} catch (NotFoundException $e) {
				$this->removeFromCache($path);
				return null;
			}
		} finally {
			if ($lock) {
				if ($this->storage->instanceOfStorage('\OCP\Files\Storage\ILockingStorage')) {
					$this->storage->releaseLock($path, ILockingProvider::LOCK_SHARED, $this->lockingProvider);
					$this->storage->releaseLock('scanner::' . $path, ILockingProvider::LOCK_EXCLUSIVE, $this->lockingProvider);
				}
			}
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
		$existingChildren = [];
		$children = $this->cache->getFolderContentsById($folderId);
		foreach ($children as $child) {
			$existingChildren[$child['name']] = $child;
		}
		return $existingChildren;
	}

	/**
	 * scan all the files and folders in a folder
	 *
	 * @param string $path
	 * @param bool|IScanner::SCAN_RECURSIVE_INCOMPLETE $recursive
	 * @param int $reuse a combination of self::REUSE_*
	 * @param int $folderId id for the folder to be scanned
	 * @param bool $lock set to false to disable getting an additional read lock during scanning
	 * @param int|float $oldSize the size of the folder before (re)scanning the children
	 * @return int|float the size of the scanned folder or -1 if the size is unknown at this stage
	 */
	protected function scanChildren(string $path, $recursive, int $reuse, int $folderId, bool $lock, int|float $oldSize) {
		if ($reuse === -1) {
			$reuse = ($recursive === self::SCAN_SHALLOW) ? self::REUSE_ETAG | self::REUSE_SIZE : self::REUSE_ETAG;
		}
		$this->emit('\OC\Files\Cache\Scanner', 'scanFolder', [$path, $this->storageId]);
		$size = 0;
		$childQueue = $this->handleChildren($path, $recursive, $reuse, $folderId, $lock, $size);

		foreach ($childQueue as $child => [$childId, $childSize]) {
			$childSize = $this->scanChildren($child, $recursive, $reuse, $childId, $lock, $childSize);
			if ($childSize === -1) {
				$size = -1;
			} elseif ($size !== -1) {
				$size += $childSize;
			}
		}

		// for encrypted storages, we trigger a regular folder size calculation instead of using the calculated size
		// to make sure we also updated the unencrypted-size where applicable
		if ($this->storage->instanceOfStorage(Encryption::class)) {
			$this->cache->calculateFolderSize($path);
		} else {
			if ($this->cacheActive && $oldSize !== $size) {
				$this->cache->update($folderId, ['size' => $size]);
			}
		}
		$this->emit('\OC\Files\Cache\Scanner', 'postScanFolder', [$path, $this->storageId]);
		return $size;
	}

	/**
	 * @param bool|IScanner::SCAN_RECURSIVE_INCOMPLETE $recursive
	 */
	private function handleChildren(string $path, $recursive, int $reuse, int $folderId, bool $lock, int|float &$size): array {
		// we put this in it's own function so it cleans up the memory before we start recursing
		$existingChildren = $this->getExistingChildren($folderId);
		$newChildren = iterator_to_array($this->storage->getDirectoryContent($path));

		if (count($existingChildren) === 0 && count($newChildren) === 0) {
			// no need to do a transaction
			return [];
		}

		if ($this->useTransactions) {
			$this->connection->beginTransaction();
		}

		$exceptionOccurred = false;
		$childQueue = [];
		$newChildNames = [];
		foreach ($newChildren as $fileMeta) {
			$permissions = $fileMeta['scan_permissions'] ?? $fileMeta['permissions'];
			if ($permissions === 0) {
				continue;
			}
			$originalFile = $fileMeta['name'];
			$file = trim(\OC\Files\Filesystem::normalizePath($originalFile), '/');
			if (trim($originalFile, '/') !== $file) {
				// encoding mismatch, might require compatibility wrapper
				\OC::$server->get(LoggerInterface::class)->debug('Scanner: Skipping non-normalized file name "'. $originalFile . '" in path "' . $path . '".', ['app' => 'core']);
				$this->emit('\OC\Files\Cache\Scanner', 'normalizedNameMismatch', [$path ? $path . '/' . $originalFile : $originalFile]);
				// skip this entry
				continue;
			}

			$newChildNames[] = $file;
			$child = $path ? $path . '/' . $file : $file;
			try {
				$existingData = $existingChildren[$file] ?? false;
				$data = $this->scanFile($child, $reuse, $folderId, $existingData, $lock, $fileMeta);
				if ($data) {
					if ($data['mimetype'] === 'httpd/unix-directory' && $recursive === self::SCAN_RECURSIVE) {
						$childQueue[$child] = [$data['fileid'], $data['size']];
					} elseif ($data['mimetype'] === 'httpd/unix-directory' && $recursive === self::SCAN_RECURSIVE_INCOMPLETE && $data['size'] === -1) {
						// only recurse into folders which aren't fully scanned
						$childQueue[$child] = [$data['fileid'], $data['size']];
					} elseif ($data['size'] === -1) {
						$size = -1;
					} elseif ($size !== -1) {
						$size += $data['size'];
					}
				}
			} catch (Exception $ex) {
				// might happen if inserting duplicate while a scanning
				// process is running in parallel
				// log and ignore
				if ($this->useTransactions) {
					$this->connection->rollback();
					$this->connection->beginTransaction();
				}
				\OC::$server->get(LoggerInterface::class)->debug('Exception while scanning file "' . $child . '"', [
					'app' => 'core',
					'exception' => $ex,
				]);
				$exceptionOccurred = true;
			} catch (\OCP\Lock\LockedException $e) {
				if ($this->useTransactions) {
					$this->connection->rollback();
				}
				throw $e;
			}
		}
		$removedChildren = \array_diff(array_keys($existingChildren), $newChildNames);
		foreach ($removedChildren as $childName) {
			$child = $path ? $path . '/' . $childName : $childName;
			$this->removeFromCache($child);
		}
		if ($this->useTransactions) {
			$this->connection->commit();
		}
		if ($exceptionOccurred) {
			// It might happen that the parallel scan process has already
			// inserted mimetypes but those weren't available yet inside the transaction
			// To make sure to have the updated mime types in such cases,
			// we reload them here
			\OC::$server->getMimeTypeLoader()->reset();
		}
		return $childQueue;
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
		if (str_contains($file, '.part/')) {
			return true;
		}

		return false;
	}

	/**
	 * walk over any folders that are not fully scanned yet and scan them
	 */
	public function backgroundScan() {
		if ($this->storage->instanceOfStorage(Jail::class)) {
			// for jail storage wrappers (shares, groupfolders) we run the background scan on the source storage
			// this is mainly done because the jail wrapper doesn't implement `getIncomplete` (because it would be inefficient).
			//
			// Running the scan on the source storage might scan more than "needed", but the unscanned files outside the jail will
			// have to be scanned at some point anyway.
			$unJailedScanner = $this->storage->getUnjailedStorage()->getScanner();
			$unJailedScanner->backgroundScan();
		} else {
			if (!$this->cache->inCache('')) {
				// if the storage isn't in the cache yet, just scan the root completely
				$this->runBackgroundScanJob(function () {
					$this->scan('', self::SCAN_RECURSIVE, self::REUSE_ETAG);
				}, '');
			} else {
				$lastPath = null;
				// find any path marked as unscanned and run the scanner until no more paths are unscanned (or we get stuck)
				while (($path = $this->cache->getIncomplete()) !== false && $path !== $lastPath) {
					$this->runBackgroundScanJob(function () use ($path) {
						$this->scan($path, self::SCAN_RECURSIVE_INCOMPLETE, self::REUSE_ETAG | self::REUSE_SIZE);
					}, $path);
					// FIXME: this won't proceed with the next item, needs revamping of getIncomplete()
					// to make this possible
					$lastPath = $path;
				}
			}
		}
	}

	protected function runBackgroundScanJob(callable $callback, $path) {
		try {
			$callback();
			\OC_Hook::emit('Scanner', 'correctFolderSize', ['path' => $path]);
			if ($this->cacheActive && $this->cache instanceof Cache) {
				$this->cache->correctFolderSize($path, null, true);
			}
		} catch (\OCP\Files\StorageInvalidException $e) {
			// skip unavailable storages
		} catch (\OCP\Files\StorageNotAvailableException $e) {
			// skip unavailable storages
		} catch (\OCP\Files\ForbiddenException $e) {
			// skip forbidden storages
		} catch (\OCP\Lock\LockedException $e) {
			// skip unavailable storages
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
