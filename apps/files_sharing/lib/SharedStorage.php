<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing;

use OC\Files\Cache\CacheDependencies;
use OC\Files\Cache\CacheEntry;
use OC\Files\Cache\FailedCache;
use OC\Files\Cache\NullWatcher;
use OC\Files\ObjectStore\HomeObjectStoreStorage;
use OC\Files\Storage\Common;
use OC\Files\Storage\FailedStorage;
use OC\Files\Storage\Home;
use OC\Files\Storage\Storage;
use OC\Files\Storage\Wrapper\Jail;
use OC\Files\Storage\Wrapper\PermissionsMask;
use OC\Files\Storage\Wrapper\Wrapper;
use OC\Files\View;
use OC\Share\Share;
use OC\User\NoUserException;
use OCA\Files_Sharing\ISharedStorage as LegacyISharedStorage;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Cache\IScanner;
use OCP\Files\Cache\IWatcher;
use OCP\Files\Folder;
use OCP\Files\IHomeStorage;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IDisableEncryptionStorage;
use OCP\Files\Storage\ILockingStorage;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\Storage\IStorage;
use OCP\Lock\ILockingProvider;
use OCP\Server;
use OCP\Share\IShare;
use OCP\Util;
use Psr\Log\LoggerInterface;

/**
 * Convert target path to source path and pass the function call to the correct storage provider
 */
class SharedStorage extends Jail implements LegacyISharedStorage, ISharedStorage, IDisableEncryptionStorage {
	/** @var IShare */
	private $superShare;

	/** @var IShare[] */
	private $groupedShares;

	/**
	 * @var View
	 */
	private $ownerView;

	private $initialized = false;

	/**
	 * @var ICacheEntry
	 */
	private $sourceRootInfo;

	/** @var string */
	private $user;

	private LoggerInterface $logger;

	/** @var IStorage */
	private $nonMaskedStorage;

	private array $mountOptions = [];

	/** @var boolean */
	private $sharingDisabledForUser;

	/** @var ?Folder $ownerUserFolder */
	private $ownerUserFolder = null;

	private string $sourcePath = '';

	private static int $initDepth = 0;

	/**
	 * @psalm-suppress NonInvariantDocblockPropertyType
	 * @var ?Storage $storage
	 */
	protected $storage;

	public function __construct(array $parameters) {
		$this->ownerView = $parameters['ownerView'];
		$this->logger = Server::get(LoggerInterface::class);

		$this->superShare = $parameters['superShare'];
		$this->groupedShares = $parameters['groupedShares'];

		$this->user = $parameters['user'];
		if (isset($parameters['sharingDisabledForUser'])) {
			$this->sharingDisabledForUser = $parameters['sharingDisabledForUser'];
		} else {
			$this->sharingDisabledForUser = false;
		}

		parent::__construct([
			'storage' => null,
			'root' => null,
		]);
	}

	/**
	 * @return ICacheEntry
	 */
	private function getSourceRootInfo() {
		if (is_null($this->sourceRootInfo)) {
			if (is_null($this->superShare->getNodeCacheEntry())) {
				$this->init();
				$this->sourceRootInfo = $this->nonMaskedStorage->getCache()->get($this->rootPath);
			} else {
				$this->sourceRootInfo = $this->superShare->getNodeCacheEntry();
			}
		}
		return $this->sourceRootInfo;
	}

	/**
	 * @psalm-assert Storage $this->storage
	 */
	private function init() {
		if ($this->initialized) {
			if (!$this->storage) {
				// marked as initialized but no storage set
				// this is probably because some code path has caused recursion during the share setup
				// we setup a "failed storage" so `getWrapperStorage` doesn't return null.
				// If the share setup completes after this the "failed storage" will be overwritten by the correct one
				$this->logger->warning('Possible share setup recursion detected');
				$this->storage = new FailedStorage(['exception' => new \Exception('Possible share setup recursion detected')]);
				$this->cache = new FailedCache();
				$this->rootPath = '';
			}
			return;
		}

		$this->initialized = true;
		self::$initDepth++;

		try {
			if (self::$initDepth > 10) {
				throw new \Exception('Maximum share depth reached');
			}

			/** @var IRootFolder $rootFolder */
			$rootFolder = Server::get(IRootFolder::class);
			$this->ownerUserFolder = $rootFolder->getUserFolder($this->superShare->getShareOwner());
			$sourceId = $this->superShare->getNodeId();
			$ownerNodes = $this->ownerUserFolder->getById($sourceId);

			if (count($ownerNodes) === 0) {
				$this->storage = new FailedStorage(['exception' => new NotFoundException("File by id $sourceId not found")]);
				$this->cache = new FailedCache();
				$this->rootPath = '';
			} else {
				foreach ($ownerNodes as $ownerNode) {
					$nonMaskedStorage = $ownerNode->getStorage();

					// check if potential source node would lead to a recursive share setup
					if ($nonMaskedStorage instanceof Wrapper && $nonMaskedStorage->isWrapperOf($this)) {
						continue;
					}
					$this->nonMaskedStorage = $nonMaskedStorage;
					$this->sourcePath = $ownerNode->getPath();
					$this->rootPath = $ownerNode->getInternalPath();
					$this->cache = null;
					break;
				}
				if (!$this->nonMaskedStorage) {
					// all potential source nodes would have been recursive
					throw new \Exception('recursive share detected');
				}
				$this->storage = new PermissionsMask([
					'storage' => $this->nonMaskedStorage,
					'mask' => $this->superShare->getPermissions(),
				]);
			}
		} catch (NotFoundException $e) {
			// original file not accessible or deleted, set FailedStorage
			$this->storage = new FailedStorage(['exception' => $e]);
			$this->cache = new FailedCache();
			$this->rootPath = '';
		} catch (NoUserException $e) {
			// sharer user deleted, set FailedStorage
			$this->storage = new FailedStorage(['exception' => $e]);
			$this->cache = new FailedCache();
			$this->rootPath = '';
		} catch (\Exception $e) {
			$this->storage = new FailedStorage(['exception' => $e]);
			$this->cache = new FailedCache();
			$this->rootPath = '';
			$this->logger->error($e->getMessage(), ['exception' => $e]);
		}

		if (!$this->nonMaskedStorage) {
			$this->nonMaskedStorage = $this->storage;
		}
		self::$initDepth--;
	}

	public function instanceOfStorage(string $class): bool {
		if ($class === '\OC\Files\Storage\Common' || $class == Common::class) {
			return true;
		}
		if (in_array($class, [
			'\OC\Files\Storage\Home',
			'\OC\Files\ObjectStore\HomeObjectStoreStorage',
			'\OCP\Files\IHomeStorage',
			Home::class,
			HomeObjectStoreStorage::class,
			IHomeStorage::class
		])) {
			return false;
		}
		return parent::instanceOfStorage($class);
	}

	/**
	 * @return string
	 */
	public function getShareId() {
		return $this->superShare->getId();
	}

	private function isValid(): bool {
		return $this->getSourceRootInfo() && ($this->getSourceRootInfo()->getPermissions() & Constants::PERMISSION_SHARE) === Constants::PERMISSION_SHARE;
	}

	public function getId(): string {
		return 'shared::' . $this->getMountPoint();
	}

	public function getPermissions(string $path = ''): int {
		if (!$this->isValid()) {
			return 0;
		}
		$permissions = parent::getPermissions($path) & $this->superShare->getPermissions();

		// part files and the mount point always have delete permissions
		if ($path === '' || pathinfo($path, PATHINFO_EXTENSION) === 'part') {
			$permissions |= Constants::PERMISSION_DELETE;
		}

		if ($this->sharingDisabledForUser) {
			$permissions &= ~Constants::PERMISSION_SHARE;
		}

		return $permissions;
	}

	public function isCreatable(string $path): bool {
		return (bool)($this->getPermissions($path) & Constants::PERMISSION_CREATE);
	}

	public function isReadable(string $path): bool {
		if (!$this->isValid()) {
			return false;
		}
		if (!$this->file_exists($path)) {
			return false;
		}
		/** @var IStorage $storage */
		/** @var string $internalPath */
		[$storage, $internalPath] = $this->resolvePath($path);
		return $storage->isReadable($internalPath);
	}

	public function isUpdatable(string $path): bool {
		return (bool)($this->getPermissions($path) & Constants::PERMISSION_UPDATE);
	}

	public function isDeletable(string $path): bool {
		return (bool)($this->getPermissions($path) & Constants::PERMISSION_DELETE);
	}

	public function isSharable(string $path): bool {
		if (Util::isSharingDisabledForUser() || !Share::isResharingAllowed()) {
			return false;
		}
		return (bool)($this->getPermissions($path) & Constants::PERMISSION_SHARE);
	}

	public function fopen(string $path, string $mode) {
		$source = $this->getUnjailedPath($path);
		switch ($mode) {
			case 'r+':
			case 'rb+':
			case 'w+':
			case 'wb+':
			case 'x+':
			case 'xb+':
			case 'a+':
			case 'ab+':
			case 'w':
			case 'wb':
			case 'x':
			case 'xb':
			case 'a':
			case 'ab':
				$creatable = $this->isCreatable(dirname($path));
				$updatable = $this->isUpdatable($path);
				// if neither permissions given, no need to continue
				if (!$creatable && !$updatable) {
					if (pathinfo($path, PATHINFO_EXTENSION) === 'part') {
						$updatable = $this->isUpdatable(dirname($path));
					}

					if (!$updatable) {
						return false;
					}
				}

				$exists = $this->file_exists($path);
				// if a file exists, updatable permissions are required
				if ($exists && !$updatable) {
					return false;
				}

				// part file is allowed if !$creatable but the final file is $updatable
				if (pathinfo($path, PATHINFO_EXTENSION) !== 'part') {
					if (!$exists && !$creatable) {
						return false;
					}
				}
		}
		$info = [
			'target' => $this->getMountPoint() . '/' . $path,
			'source' => $source,
			'mode' => $mode,
		];
		Util::emitHook('\OC\Files\Storage\Shared', 'fopen', $info);
		return $this->nonMaskedStorage->fopen($this->getUnjailedPath($path), $mode);
	}

	public function rename(string $source, string $target): bool {
		$this->init();
		$isPartFile = pathinfo($source, PATHINFO_EXTENSION) === 'part';
		$targetExists = $this->file_exists($target);
		$sameFolder = dirname($source) === dirname($target);

		if ($targetExists || ($sameFolder && !$isPartFile)) {
			if (!$this->isUpdatable('')) {
				return false;
			}
		} else {
			if (!$this->isCreatable('')) {
				return false;
			}
		}

		return $this->nonMaskedStorage->rename($this->getUnjailedPath($source), $this->getUnjailedPath($target));
	}

	/**
	 * return mount point of share, relative to data/user/files
	 *
	 * @return string
	 */
	public function getMountPoint(): string {
		return $this->superShare->getTarget();
	}

	public function setMountPoint(string $path): void {
		$this->superShare->setTarget($path);

		foreach ($this->groupedShares as $share) {
			$share->setTarget($path);
		}
	}

	/**
	 * get the user who shared the file
	 *
	 * @return string
	 */
	public function getSharedFrom(): string {
		return $this->superShare->getShareOwner();
	}

	public function getShare(): IShare {
		return $this->superShare;
	}

	/**
	 * return share type, can be "file" or "folder"
	 *
	 * @return string
	 */
	public function getItemType(): string {
		return $this->superShare->getNodeType();
	}

	public function getCache(string $path = '', ?IStorage $storage = null): ICache {
		if ($this->cache) {
			return $this->cache;
		}
		if (!$storage) {
			$storage = $this;
		}
		$sourceRoot = $this->getSourceRootInfo();
		if ($this->storage instanceof FailedStorage) {
			return new FailedCache();
		}

		$this->cache = new Cache(
			$storage,
			$sourceRoot,
			Server::get(CacheDependencies::class),
			$this->getShare()
		);
		return $this->cache;
	}

	public function getScanner(string $path = '', ?IStorage $storage = null): IScanner {
		if (!$storage) {
			$storage = $this;
		}
		return new Scanner($storage);
	}

	public function getOwner(string $path): string|false {
		return $this->superShare->getShareOwner();
	}

	public function getWatcher(string $path = '', ?IStorage $storage = null): IWatcher {
		if ($this->watcher) {
			return $this->watcher;
		}

		// Get node information
		$node = $this->getShare()->getNodeCacheEntry();
		if ($node instanceof CacheEntry) {
			$storageId = $node->getData()['storage_string_id'] ?? null;
			// for shares from the home storage we can rely on the home storage to keep itself up to date
			// for other storages we need use the proper watcher
			if ($storageId !== null && !(str_starts_with($storageId, 'home::') || str_starts_with($storageId, 'object::user'))) {
				$cache = $this->getCache();
				$this->watcher = parent::getWatcher($path, $storage);
				if ($cache instanceof Cache) {
					$this->watcher->onUpdate($cache->markRootChanged(...));
				}
				return $this->watcher;
			}
		}

		// cache updating is handled by the share source
		$this->watcher = new NullWatcher();
		return $this->watcher;
	}

	/**
	 * unshare complete storage, also the grouped shares
	 *
	 * @return bool
	 */
	public function unshareStorage(): bool {
		foreach ($this->groupedShares as $share) {
			Server::get(\OCP\Share\IManager::class)->deleteFromSelf($share, $this->user);
		}
		return true;
	}

	public function acquireLock(string $path, int $type, ILockingProvider $provider): void {
		/** @var ILockingStorage $targetStorage */
		[$targetStorage, $targetInternalPath] = $this->resolvePath($path);
		$targetStorage->acquireLock($targetInternalPath, $type, $provider);
		// lock the parent folders of the owner when locking the share as recipient
		if ($path === '') {
			$sourcePath = $this->ownerUserFolder->getRelativePath($this->sourcePath);
			$this->ownerView->lockFile(dirname($sourcePath), ILockingProvider::LOCK_SHARED, true);
		}
	}

	public function releaseLock(string $path, int $type, ILockingProvider $provider): void {
		/** @var ILockingStorage $targetStorage */
		[$targetStorage, $targetInternalPath] = $this->resolvePath($path);
		$targetStorage->releaseLock($targetInternalPath, $type, $provider);
		// unlock the parent folders of the owner when unlocking the share as recipient
		if ($path === '') {
			$sourcePath = $this->ownerUserFolder->getRelativePath($this->sourcePath);
			$this->ownerView->unlockFile(dirname($sourcePath), ILockingProvider::LOCK_SHARED, true);
		}
	}

	public function changeLock(string $path, int $type, ILockingProvider $provider): void {
		/** @var ILockingStorage $targetStorage */
		[$targetStorage, $targetInternalPath] = $this->resolvePath($path);
		$targetStorage->changeLock($targetInternalPath, $type, $provider);
	}

	public function getAvailability(): array {
		// shares do not participate in availability logic
		return [
			'available' => true,
			'last_checked' => 0,
		];
	}

	public function setAvailability(bool $isAvailable): void {
		// shares do not participate in availability logic
	}

	public function getSourceStorage() {
		$this->init();
		return $this->nonMaskedStorage;
	}

	public function getWrapperStorage(): Storage {
		$this->init();

		/**
		 * @psalm-suppress DocblockTypeContradiction
		 */
		if (!$this->storage) {
			$message = 'no storage set after init for share ' . $this->getShareId();
			$this->logger->error($message);
			$this->storage = new FailedStorage(['exception' => new \Exception($message)]);
		}

		return $this->storage;
	}

	public function file_get_contents(string $path): string|false {
		$info = [
			'target' => $this->getMountPoint() . '/' . $path,
			'source' => $this->getUnjailedPath($path),
		];
		Util::emitHook('\OC\Files\Storage\Shared', 'file_get_contents', $info);
		return parent::file_get_contents($path);
	}

	public function file_put_contents(string $path, mixed $data): int|float|false {
		$info = [
			'target' => $this->getMountPoint() . '/' . $path,
			'source' => $this->getUnjailedPath($path),
		];
		Util::emitHook('\OC\Files\Storage\Shared', 'file_put_contents', $info);
		return parent::file_put_contents($path, $data);
	}

	public function setMountOptions(array $options): void {
		/* Note: This value is never read */
		$this->mountOptions = $options;
	}

	public function getUnjailedPath(string $path): string {
		$this->init();
		return parent::getUnjailedPath($path);
	}

	public function getDirectDownload(string $path): array|false {
		// disable direct download for shares
		return [];
	}
}
