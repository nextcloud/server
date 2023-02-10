<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Stefan Weil <sw@weilnetz.de>
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

namespace OC\Files\Node;

use OCP\Cache\CappedMemoryCache;
use OC\Files\FileInfo;
use OC\Files\Mount\Manager;
use OC\Files\Mount\MountPoint;
use OC\Files\Utils\PathHelper;
use OC\Files\View;
use OC\Hooks\PublicEmitter;
use OC\User\NoUserException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Events\Node\FilesystemTornDownEvent;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IUser;
use OCP\IUserManager;
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
	private Manager $mountManager;
	private PublicEmitter $emitter;
	private ?IUser $user;
	private CappedMemoryCache $userFolderCache;
	private IUserMountCache $userMountCache;
	private LoggerInterface $logger;
	private IUserManager $userManager;
	private IEventDispatcher $eventDispatcher;

	/**
	 * @param Manager $manager
	 * @param View $view
	 * @param IUser|null $user
	 */
	public function __construct(
		$manager,
		$view,
		$user,
		IUserMountCache $userMountCache,
		LoggerInterface $logger,
		IUserManager $userManager,
		IEventDispatcher $eventDispatcher
	) {
		parent::__construct($this, $view, '');
		$this->mountManager = $manager;
		$this->user = $user;
		$this->emitter = new PublicEmitter();
		$this->userFolderCache = new CappedMemoryCache();
		$this->userMountCache = $userMountCache;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$eventDispatcher->addListener(FilesystemTornDownEvent::class, function () {
			$this->userFolderCache = new CappedMemoryCache();
		});
	}

	/**
	 * Get the user for which the filesystem is setup
	 *
	 * @return \OC\User\User
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @param string $scope
	 * @param string $method
	 * @param callable $callback
	 */
	public function listen($scope, $method, callable $callback) {
		$this->emitter->listen($scope, $method, $callback);
	}

	/**
	 * @param string $scope optional
	 * @param string $method optional
	 * @param callable $callback optional
	 */
	public function removeListener($scope = null, $method = null, callable $callback = null) {
		$this->emitter->removeListener($scope, $method, $callback);
	}

	/**
	 * @param string $scope
	 * @param string $method
	 * @param Node[] $arguments
	 */
	public function emit($scope, $method, $arguments = []) {
		$this->emitter->emit($scope, $method, $arguments);
	}

	/**
	 * @param \OC\Files\Storage\Storage $storage
	 * @param string $mountPoint
	 * @param array $arguments
	 */
	public function mount($storage, $mountPoint, $arguments = []) {
		$mount = new MountPoint($storage, $mountPoint, $arguments);
		$this->mountManager->addMount($mount);
	}

	/**
	 * @param string $mountPoint
	 * @return \OC\Files\Mount\MountPoint
	 */
	public function getMount($mountPoint) {
		return $this->mountManager->find($mountPoint);
	}

	/**
	 * @param string $mountPoint
	 * @return \OC\Files\Mount\MountPoint[]
	 */
	public function getMountsIn($mountPoint) {
		return $this->mountManager->findIn($mountPoint);
	}

	/**
	 * @param string $storageId
	 * @return \OC\Files\Mount\MountPoint[]
	 */
	public function getMountByStorageId($storageId) {
		return $this->mountManager->findByStorageId($storageId);
	}

	/**
	 * @param int $numericId
	 * @return MountPoint[]
	 */
	public function getMountByNumericStorageId($numericId) {
		return $this->mountManager->findByNumericId($numericId);
	}

	/**
	 * @param \OC\Files\Mount\MountPoint $mount
	 */
	public function unMount($mount) {
		$this->mountManager->remove($mount);
	}

	/**
	 * @param string $path
	 * @return Node
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\Files\NotFoundException
	 */
	public function get($path) {
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

	//most operations can't be done on the root

	/**
	 * @param string $targetPath
	 * @return Node
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function rename($targetPath) {
		throw new NotPermittedException();
	}

	public function delete() {
		throw new NotPermittedException();
	}

	/**
	 * @param string $targetPath
	 * @return Node
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function copy($targetPath) {
		throw new NotPermittedException();
	}

	/**
	 * @param int $mtime
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function touch($mtime = null) {
		throw new NotPermittedException();
	}

	/**
	 * @return \OC\Files\Storage\Storage
	 * @throws \OCP\Files\NotFoundException
	 */
	public function getStorage() {
		throw new NotFoundException();
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return '/';
	}

	/**
	 * @return string
	 */
	public function getInternalPath() {
		return '';
	}

	/**
	 * @return int
	 */
	public function getId() {
		return 0;
	}

	/**
	 * @return array
	 */
	public function stat() {
		return [];
	}

	/**
	 * @return int
	 */
	public function getMTime() {
		return 0;
	}

	/**
	 * @param bool $includeMounts
	 * @return int|float
	 */
	public function getSize($includeMounts = true): int|float {
		return 0;
	}

	/**
	 * @return string
	 */
	public function getEtag() {
		return '';
	}

	/**
	 * @return int
	 */
	public function getPermissions() {
		return \OCP\Constants::PERMISSION_CREATE;
	}

	/**
	 * @return bool
	 */
	public function isReadable() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function isUpdateable() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function isDeletable() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function isShareable() {
		return false;
	}

	/**
	 * @return Node
	 * @throws \OCP\Files\NotFoundException
	 */
	public function getParent() {
		throw new NotFoundException();
	}

	/**
	 * @return string
	 */
	public function getName() {
		return '';
	}

	/**
	 * Returns a view to user's files folder
	 *
	 * @param string $userId user ID
	 * @return \OCP\Files\Folder
	 * @throws NoUserException
	 * @throws NotPermittedException
	 */
	public function getUserFolder($userId) {
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
						throw new \Exception("User folder for $userId exists as a file");
					}
				} catch (NotFoundException $e) {
					if (!$this->nodeExists('/' . $userId)) {
						$this->newFolder('/' . $userId);
					}
					$folder = $this->newFolder('/' . $userId . '/files');
				}
			} else {
				$folder = new LazyUserFolder($this, $userObject);
			}

			$this->userFolderCache->set($userId, $folder);
		}

		return $this->userFolderCache->get($userId);
	}

	public function getUserMountCache() {
		return $this->userMountCache;
	}

	/**
	 * @param int $id
	 * @return Node[]
	 */
	public function getByIdInPath(int $id, string $path): array {
		$mountCache = $this->getUserMountCache();
		if (strpos($path, '/', 1) > 0) {
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
					throw new \Exception("getByIdInPath with non folder");
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
			return $this->createNode($absolutePath, new FileInfo(
				$absolutePath, $mount->getStorage(), $cacheEntry->getPath(), $cacheEntry, $mount,
				\OC::$server->getUserManager()->get($mount->getStorage()->getOwner($pathRelativeToMount))
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
}
