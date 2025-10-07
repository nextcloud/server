<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Node;

use OC\Files\FileInfo;
use OC\Files\Mount\Manager;
use OC\Files\Mount\MountPoint;
use OC\Files\Utils\PathHelper;
use OC\Files\View;
use OC\Hooks\PublicEmitter;
use OC\User\NoUserException;
use OCA\Files\AppInfo\Application;
use OCA\Files\ConfigLexicon;
use OCP\Cache\CappedMemoryCache;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Events\Node\FilesystemTornDownEvent;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Node as INode;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\IStorage;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use Override;
use Psr\Log\LoggerInterface;

/**
 * Class Root
 *
 * Hooks available in scope \OC\Files
 * - preWrite(\OCP\Files\Node $node)
 * - postWrite(\OCP\Files\Node $node)
 * - preCreate(\OCP\Files\Node $node)
 * - postCreate(\OCP\Files\Node $node)
 * - preDelete(\OCP\Files\Node $node)
 * - postDelete(\OCP\Files\Node $node)
 * - preTouch(\OC\FilesP\Node $node, int $mtime)
 * - postTouch(\OCP\Files\Node $node)
 * - preCopy(\OCP\Files\Node $source, \OCP\Files\Node $target)
 * - postCopy(\OCP\Files\Node $source, \OCP\Files\Node $target)
 * - preRename(\OCP\Files\Node $source, \OCP\Files\Node $target)
 * - postRename(\OCP\Files\Node $source, \OCP\Files\Node $target)
 *
 * @package OC\Files\Node
 */
class Root extends Folder implements IRootFolder {
	private PublicEmitter $emitter;
	private CappedMemoryCache $userFolderCache;
	private ICache $pathByIdCache;
	private bool $useDefaultHomeFoldersPermissions = true;

	public function __construct(
		private Manager $mountManager,
		View $view,
		private ?IUser $user,
		private IUserMountCache $userMountCache,
		private LoggerInterface $logger,
		private IUserManager $userManager,
		IEventDispatcher $eventDispatcher,
		ICacheFactory $cacheFactory,
		IAppConfig $appConfig,
	) {
		parent::__construct($this, $view, '');
		$this->emitter = new PublicEmitter();
		$this->userFolderCache = new CappedMemoryCache();
		$eventDispatcher->addListener(FilesystemTornDownEvent::class, function () {
			$this->userFolderCache = new CappedMemoryCache();
		});
		$this->pathByIdCache = $cacheFactory->createLocal('path-by-id');
		$this->useDefaultHomeFoldersPermissions = count($appConfig->getValueArray(Application::APP_ID, ConfigLexicon::OVERWRITES_HOME_FOLDERS)) === 0;
	}

	/**
	 * @internal Only used in unit tests
	 */
	public function getUser(): ?IUser {
		return $this->user;
	}

	#[Override]
	public function listen($scope, $method, callable $callback) {
		$this->emitter->listen($scope, $method, $callback);
	}

	#[Override]
	public function removeListener($scope = null, $method = null, ?callable $callback = null) {
		$this->emitter->removeListener($scope, $method, $callback);
	}

	public function emit(string $scope, string $method, array $arguments = []) {
		$this->emitter->emit($scope, $method, $arguments);
	}

	public function mount(IStorage $storage, string $mountPoint, array $arguments = []) {
		$mount = new MountPoint($storage, $mountPoint, $arguments);
		$this->mountManager->addMount($mount);
	}

	#[Override]
	public function getMount(string $mountPoint): IMountPoint {
		return $this->mountManager->find($mountPoint);
	}

	#[Override]
	public function getMountsIn(string $mountPoint): array {
		return $this->mountManager->findIn($mountPoint);
	}

	public function get(string $path): \OCP\Files\Node {
		$path = $this->normalizePath($path);
		if ($this->isValidPath($path)) {
			$fullPath = $this->getFullPath($path);
			$fileInfo = $this->view->getFileInfo($fullPath, false);
			if ($fileInfo) {
				return $this->createNode($fullPath, $fileInfo, false);
			} else {
				throw new NotFoundException($path);
			}
		} else {
			throw new NotPermittedException();
		}
	}

	// most operations can't be done on the root

	#[Override]
	public function move(string $targetPath): \OCP\Files\Node {
		throw new NotPermittedException();
	}

	#[Override]
	public function delete(): void {
		throw new NotPermittedException();
	}

	#[Override]
	public function copy(string $targetPath): \OCP\Files\Node {
		throw new NotPermittedException();
	}

	#[Override]
	public function touch(?int $mtime = null): void {
		throw new NotPermittedException();
	}

	#[Override]
	public function getStorage(): IStorage {
		throw new NotFoundException();
	}

	#[Override]
	public function getPath(): string {
		return '/';
	}

	#[Override]
	public function getInternalPath(): string {
		return '';
	}

	#[Override]
	public function getId(): int {
		return 0;
	}

	#[Override]
	public function stat(): array {
		return [];
	}

	#[Override]
	public function getMTime(): int {
		return 0;
	}

	#[Override]
	public function getSize(bool $includeMounts = true): int|float {
		return 0;
	}

	#[Override]
	public function getEtag(): string {
		return '';
	}

	#[Override]
	public function getPermissions(): int {
		return \OCP\Constants::PERMISSION_CREATE;
	}

	#[Override]
	public function isReadable(): bool {
		return false;
	}

	#[Override]
	public function isUpdateable(): bool {
		return false;
	}

	#[Override]
	public function isDeletable(): bool {
		return false;
	}

	#[Override]
	public function isShareable(): bool {
		return false;
	}

	#[Override]
	public function getParent(): \OCP\Files\Folder|IRootFolder {
		throw new NotFoundException();
	}

	#[Override]
	public function getName(): string {
		return '';
	}

	#[Override]
	public function getUserFolder(string $userId): \OCP\Files\Folder {
		$userObject = $this->userManager->get($userId);

		if (is_null($userObject)) {
			$e = new NoUserException('Backends provided no user object');
			$this->logger->error(
				sprintf(
					'Backends provided no user object for %s',
					$userId
				),
				[
					'app' => 'files',
					'exception' => $e,
				]
			);
			throw $e;
		}

		$userId = $userObject->getUID();

		if (!$this->userFolderCache->hasKey($userId)) {
			if ($this->mountManager->getSetupManager()->isSetupComplete($userObject)) {
				try {
					$folder = $this->get('/' . $userId . '/files');
					if (!$folder instanceof \OCP\Files\Folder) {
						throw new \Exception("Account folder for \"$userId\" exists as a file");
					}
				} catch (NotFoundException $e) {
					if (!$this->nodeExists('/' . $userId)) {
						$this->newFolder('/' . $userId);
					}
					$folder = $this->newFolder('/' . $userId . '/files');
				}
			} else {
				$folder = new LazyUserFolder($this, $userObject, $this->mountManager, $this->useDefaultHomeFoldersPermissions);
			}

			$this->userFolderCache->set($userId, $folder);
		}

		return $this->userFolderCache->get($userId);
	}

	public function getUserMountCache(): IUserMountCache {
		return $this->userMountCache;
	}

	#[Override]
	public function getFirstNodeByIdInPath(int $id, string $path): ?INode {
		// scope the cache by user, so we don't return nodes for different users
		if ($this->user) {
			$cachedPath = $this->pathByIdCache->get($this->user->getUID() . '::' . $id);
			if ($cachedPath && str_starts_with($cachedPath, $path)) {
				// getting the node by path is significantly cheaper than finding it by id
				try {
					$node = $this->get($cachedPath);
					// by validating that the cached path still has the requested fileid we can work around the need to invalidate the cached path
					// if the cached path is invalid or a different file now we fall back to the uncached logic
					if ($node->getId() === $id) {
						return $node;
					}
				} catch (NotFoundException|NotPermittedException) {
					// The file may be moved but the old path still in cache
				}
			}
		}
		$node = current($this->getByIdInPath($id, $path));
		if (!$node) {
			return null;
		}

		if ($this->user) {
			$this->pathByIdCache->set($this->user->getUID() . '::' . $id, $node->getPath());
		}
		return $node;
	}

	#[Override]
	public function getByIdInPath(int $id, string $path): array {
		$mountCache = $this->getUserMountCache();
		if ($path !== '' && strpos($path, '/', 1) > 0) {
			[, $user] = explode('/', $path);
		} else {
			$user = null;
		}
		$mountsContainingFile = $mountCache->getMountsForFileId($id, $user);

		// if the mount isn't in the cache yet, perform a setup first, then try again
		if (count($mountsContainingFile) === 0) {
			$this->mountManager->getSetupManager()->setupForPath($path, true);
			$mountsContainingFile = $mountCache->getMountsForFileId($id, $user);
		}

		// when a user has access through the same storage through multiple paths
		// (such as an external storage that is both mounted for a user and shared to the user)
		// the mount cache will only hold a single entry for the storage
		// this can lead to issues as the different ways the user has access to a storage can have different permissions
		//
		// so instead of using the cached entries directly, we instead filter the current mounts by the rootid of the cache entry

		$mountRootIds = array_map(function ($mount) {
			return $mount->getRootId();
		}, $mountsContainingFile);
		$mountRootPaths = array_map(function ($mount) {
			return $mount->getRootInternalPath();
		}, $mountsContainingFile);
		$mountProviders = array_unique(array_map(function ($mount) {
			return $mount->getMountProvider();
		}, $mountsContainingFile));
		$mountRoots = array_combine($mountRootIds, $mountRootPaths);

		$mounts = $this->mountManager->getMountsByMountProvider($path, $mountProviders);

		$mountsContainingFile = array_filter($mounts, function ($mount) use ($mountRoots) {
			return isset($mountRoots[$mount->getStorageRootId()]);
		});

		if (count($mountsContainingFile) === 0) {
			if ($user === $this->getAppDataDirectoryName()) {
				$folder = $this->get($path);
				if ($folder instanceof Folder) {
					return $folder->getByIdInRootMount($id);
				} else {
					throw new \Exception('getByIdInPath with non folder');
				}
			}
			return [];
		}

		$nodes = array_map(function (IMountPoint $mount) use ($id, $mountRoots) {
			$rootInternalPath = $mountRoots[$mount->getStorageRootId()];
			$cacheEntry = $mount->getStorage()->getCache()->get($id);
			if (!$cacheEntry) {
				return null;
			}

			// cache jails will hide the "true" internal path
			$internalPath = ltrim($rootInternalPath . '/' . $cacheEntry->getPath(), '/');
			$pathRelativeToMount = substr($internalPath, strlen($rootInternalPath));
			$pathRelativeToMount = ltrim($pathRelativeToMount, '/');
			$absolutePath = rtrim($mount->getMountPoint() . $pathRelativeToMount, '/');
			$storage = $mount->getStorage();
			if ($storage === null) {
				return null;
			}
			$ownerId = $storage->getOwner($pathRelativeToMount);
			if ($ownerId !== false) {
				$owner = Server::get(IUserManager::class)->get($ownerId);
			} else {
				$owner = null;
			}
			return $this->createNode($absolutePath, new FileInfo(
				$absolutePath,
				$storage,
				$cacheEntry->getPath(),
				$cacheEntry,
				$mount,
				$owner,
			));
		}, $mountsContainingFile);

		$nodes = array_filter($nodes);

		$folders = array_filter($nodes, function (Node $node) use ($path) {
			return PathHelper::getRelativePath($path, $node->getPath()) !== null;
		});
		usort($folders, function ($a, $b) {
			return $b->getPath() <=> $a->getPath();
		});
		return $folders;
	}

	#[Override]
	public function getNodeFromCacheEntryAndMount(ICacheEntry $cacheEntry, IMountPoint $mountPoint): INode {
		$path = $cacheEntry->getPath();
		$fullPath = $mountPoint->getMountPoint() . $path;
		// todo: LazyNode?
		$info = new FileInfo($fullPath, $mountPoint->getStorage(), $path, $cacheEntry, $mountPoint);
		$parentPath = dirname($fullPath);
		$parent = new LazyFolder($this, function () use ($parentPath) {
			$parent = $this->get($parentPath);
			if ($parent instanceof \OCP\Files\Folder) {
				return $parent;
			} else {
				throw new \Exception("parent $parentPath is not a folder");
			}
		}, [
			'path' => $parentPath,
		]);
		$isDir = $info->getType() === FileInfo::TYPE_FOLDER;
		$view = new View('');
		if ($isDir) {
			return new Folder($this, $view, $fullPath, $info, $parent);
		} else {
			return new File($this, $view, $fullPath, $info, $parent);
		}
	}
}
