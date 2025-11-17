<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Utils;

use OC\Files\Cache\Cache;
use OC\Files\Filesystem;
use OC\Files\Mount\MountPoint;
use OC\Files\Storage\FailedStorage;
use OC\Files\Storage\Home;
use OC\ForbiddenException;
use OC\Hooks\PublicEmitter;
use OC\Lock\DBLockingProvider;
use OCA\Files_Sharing\SharedStorage;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\BeforeFileScannedEvent;
use OCP\Files\Events\BeforeFolderScannedEvent;
use OCP\Files\Events\FileCacheUpdated;
use OCP\Files\Events\FileScannedEvent;
use OCP\Files\Events\FolderScannedEvent;
use OCP\Files\Events\NodeAddedToCache;
use OCP\Files\Events\NodeRemovedFromCache;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\Files\StorageNotAvailableException;
use OCP\IDBConnection;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Class Scanner
 *
 * Hooks available in scope \OC\Utils\Scanner
 *  - scanFile(string $absolutePath)
 *  - scanFolder(string $absolutePath)
 *
 * @package OC\Files\Utils
 */
class Scanner extends PublicEmitter {
	public const MAX_ENTRIES_TO_COMMIT = 10000;

	/**
	 * Whether to use a DB transaction
	 *
	 * @var bool
	 */
	protected $useTransaction;

	/**
	 * Number of entries scanned to commit
	 *
	 * @var int
	 */
	protected $entriesToCommit;

	/**
	 * @param string $user
	 * @param IDBConnection|null $db
	 * @param IEventDispatcher $dispatcher
	 */
	public function __construct(
		private $user,
		protected $db,
		private IEventDispatcher $dispatcher,
		protected LoggerInterface $logger,
	) {
		// when DB locking is used, no DB transactions will be used
		$this->useTransaction = !(Server::get(ILockingProvider::class) instanceof DBLockingProvider);
	}

	/**
	 * get all storages for $dir
	 *
	 * @param string $dir
	 * @return array<string, IMountPoint>
	 */
	protected function getMounts($dir) {
		//TODO: move to the node based fileapi once that's done
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($this->user);

		$mountManager = Filesystem::getMountManager();
		$mounts = $mountManager->findIn($dir);
		$mounts[] = $mountManager->find($dir);
		$mounts = array_reverse($mounts); //start with the mount of $dir
		$mountPoints = array_map(fn ($mount) => $mount->getMountPoint(), $mounts);

		return array_combine($mountPoints, $mounts);
	}

	/**
	 * attach listeners to the scanner
	 *
	 * @param MountPoint $mount
	 */
	protected function attachListener($mount) {
		/** @var \OC\Files\Cache\Scanner $scanner */
		$scanner = $mount->getStorage()->getScanner();
		$scanner->listen('\OC\Files\Cache\Scanner', 'scanFile', function ($path) use ($mount): void {
			$this->emit('\OC\Files\Utils\Scanner', 'scanFile', [$mount->getMountPoint() . $path]);
			$this->dispatcher->dispatchTyped(new BeforeFileScannedEvent($mount->getMountPoint() . $path));
		});
		$scanner->listen('\OC\Files\Cache\Scanner', 'scanFolder', function ($path) use ($mount): void {
			$this->emit('\OC\Files\Utils\Scanner', 'scanFolder', [$mount->getMountPoint() . $path]);
			$this->dispatcher->dispatchTyped(new BeforeFolderScannedEvent($mount->getMountPoint() . $path));
		});
		$scanner->listen('\OC\Files\Cache\Scanner', 'postScanFile', function ($path) use ($mount): void {
			$this->emit('\OC\Files\Utils\Scanner', 'postScanFile', [$mount->getMountPoint() . $path]);
			$this->dispatcher->dispatchTyped(new FileScannedEvent($mount->getMountPoint() . $path));
		});
		$scanner->listen('\OC\Files\Cache\Scanner', 'postScanFolder', function ($path) use ($mount): void {
			$this->emit('\OC\Files\Utils\Scanner', 'postScanFolder', [$mount->getMountPoint() . $path]);
			$this->dispatcher->dispatchTyped(new FolderScannedEvent($mount->getMountPoint() . $path));
		});
		$scanner->listen('\OC\Files\Cache\Scanner', 'normalizedNameMismatch', function ($path) use ($mount): void {
			$this->emit('\OC\Files\Utils\Scanner', 'normalizedNameMismatch', [$path]);
		});
	}

	/**
	 * @param string $dir
	 */
	public function backgroundScan($dir) {
		$mounts = $this->getMounts($dir);
		foreach ($mounts as $mount) {
			try {
				$storage = $mount->getStorage();
				if (is_null($storage)) {
					continue;
				}

				// don't bother scanning failed storages (shortcut for same result)
				if ($storage->instanceOfStorage(FailedStorage::class)) {
					continue;
				}

				/** @var \OC\Files\Cache\Scanner $scanner */
				$scanner = $storage->getScanner();
				$this->attachListener($mount);

				$scanner->listen('\OC\Files\Cache\Scanner', 'removeFromCache', function ($path) use ($storage): void {
					$this->triggerPropagator($storage, $path);
				});
				$scanner->listen('\OC\Files\Cache\Scanner', 'updateCache', function ($path) use ($storage): void {
					$this->triggerPropagator($storage, $path);
				});
				$scanner->listen('\OC\Files\Cache\Scanner', 'addToCache', function ($path) use ($storage): void {
					$this->triggerPropagator($storage, $path);
				});

				$propagator = $storage->getPropagator();
				$propagator->beginBatch();
				$scanner->backgroundScan();
				$propagator->commitBatch();
			} catch (\Exception $e) {
				$this->logger->error("Error while trying to scan mount as {$mount->getMountPoint()}:" . $e->getMessage(), ['exception' => $e, 'app' => 'files']);
			}
		}
	}

	/**
	 * @param string $dir
	 * @param $recursive
	 * @param callable|null $mountFilter
	 * @throws ForbiddenException
	 * @throws NotFoundException
	 */
	public function scan($dir = '', $recursive = \OC\Files\Cache\Scanner::SCAN_RECURSIVE, ?callable $mountFilter = null) {
		if (!Filesystem::isValidPath($dir)) {
			throw new \InvalidArgumentException('Invalid path to scan');
		}
		$mounts = $this->getMounts($dir);
		foreach ($mounts as $mount) {
			if ($mountFilter && !$mountFilter($mount)) {
				continue;
			}
			$storage = $mount->getStorage();
			if (is_null($storage)) {
				continue;
			}

			// don't bother scanning failed storages (shortcut for same result)
			if ($storage->instanceOfStorage(FailedStorage::class)) {
				continue;
			}

			// if the home storage isn't writable then the scanner is run as the wrong user
			if ($storage->instanceOfStorage(Home::class)) {
				/** @var Home $storage */
				foreach (['', 'files'] as $path) {
					if (!$storage->isCreatable($path)) {
						$fullPath = $storage->getSourcePath($path);
						if (isset($mounts[$mount->getMountPoint() . $path . '/'])) {
							// /<user>/files is overwritten by a mountpoint, so this check is irrelevant
							break;
						} elseif (!$storage->is_dir($path) && $storage->getCache()->inCache($path)) {
							throw new NotFoundException("User folder $fullPath exists in cache but not on disk");
						} elseif ($storage->is_dir($path)) {
							$ownerUid = fileowner($fullPath);
							$owner = posix_getpwuid($ownerUid);
							$owner = $owner['name'] ?? $ownerUid;
							$permissions = decoct(fileperms($fullPath));
							throw new ForbiddenException("User folder $fullPath is not writable, folders is owned by $owner and has mode $permissions");
						} else {
							// if the root exists in neither the cache nor the storage the user isn't setup yet
							break 2;
						}
					}
				}
			}

			// don't scan received local shares, these can be scanned when scanning the owner's storage
			if ($storage->instanceOfStorage(SharedStorage::class)) {
				continue;
			}
			$relativePath = $mount->getInternalPath($dir);
			/** @var \OC\Files\Cache\Scanner $scanner */
			$scanner = $storage->getScanner();
			$scanner->setUseTransactions(false);
			$this->attachListener($mount);

			$scanner->listen('\OC\Files\Cache\Scanner', 'removeFromCache', function ($path) use ($storage): void {
				$this->postProcessEntry($storage, $path);
				$this->dispatcher->dispatchTyped(new NodeRemovedFromCache($storage, $path));
			});
			$scanner->listen('\OC\Files\Cache\Scanner', 'updateCache', function ($path) use ($storage): void {
				$this->postProcessEntry($storage, $path);
				$this->dispatcher->dispatchTyped(new FileCacheUpdated($storage, $path));
			});
			$scanner->listen('\OC\Files\Cache\Scanner', 'addToCache', function ($path, $storageId, $data, $fileId) use ($storage): void {
				$this->postProcessEntry($storage, $path);
				if ($fileId) {
					$this->dispatcher->dispatchTyped(new FileCacheUpdated($storage, $path));
				} else {
					$this->dispatcher->dispatchTyped(new NodeAddedToCache($storage, $path));
				}
			});

			if (!$storage->file_exists($relativePath)) {
				throw new NotFoundException($dir);
			}

			if ($this->useTransaction) {
				$this->db->beginTransaction();
			}
			try {
				$propagator = $storage->getPropagator();
				$propagator->beginBatch();
				try {
					$scanner->scan($relativePath, $recursive, \OC\Files\Cache\Scanner::REUSE_ETAG | \OC\Files\Cache\Scanner::REUSE_SIZE);
				} catch (LockedException $e) {
					if (is_string($e->getReadablePath()) && str_starts_with($e->getReadablePath(), 'scanner::')) {
						throw new LockedException("scanner::$dir", $e, $e->getExistingLock());
					} else {
						throw $e;
					}
				}
				$cache = $storage->getCache();
				if ($cache instanceof Cache) {
					// only re-calculate for the root folder we scanned, anything below that is taken care of by the scanner
					$cache->correctFolderSize($relativePath);
				}
				$propagator->commitBatch();
			} catch (StorageNotAvailableException $e) {
				$this->logger->error('Storage ' . $storage->getId() . ' not available', ['exception' => $e]);
				$this->emit('\OC\Files\Utils\Scanner', 'StorageNotAvailable', [$e]);
			}
			if ($this->useTransaction) {
				$this->db->commit();
			}
		}
	}

	private function triggerPropagator(IStorage $storage, $internalPath) {
		$storage->getPropagator()->propagateChange($internalPath, time());
	}

	private function postProcessEntry(IStorage $storage, $internalPath) {
		$this->triggerPropagator($storage, $internalPath);
		if ($this->useTransaction) {
			$this->entriesToCommit++;
			if ($this->entriesToCommit >= self::MAX_ENTRIES_TO_COMMIT) {
				$propagator = $storage->getPropagator();
				$this->entriesToCommit = 0;
				$this->db->commit();
				$propagator->commitBatch();
				$this->db->beginTransaction();
				$propagator->beginBatch();
			}
		}
	}
}
