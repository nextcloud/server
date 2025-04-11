<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing;

use OC\Files\Filesystem;
use OC\Files\Mount\MountPoint;
use OC\Files\Mount\MoveableMount;
use OC\Files\View;
use OCA\Files_Sharing\Exceptions\BrokenPath;
use OCP\Cache\CappedMemoryCache;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\InvalidateMountCacheEvent;
use OCP\Files\Storage\IStorageFactory;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
use OCP\Share\Events\VerifyMountPointEvent;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

/**
 * Shared mount points can be moved by the user
 */
class SharedMount extends MountPoint implements MoveableMount, ISharedMountPoint {
	/**
	 * @var SharedStorage $storage
	 */
	protected $storage = null;

	/** @var IShare */
	private $superShare;

	/** @var IShare[] */
	private $groupedShares;

	public function __construct(
		$storage,
		array $mountpoints,
		$arguments,
		IStorageFactory $loader,
		private View $recipientView,
		CappedMemoryCache $folderExistCache,
		private IEventDispatcher $eventDispatcher,
		private IUser $user,
		bool $alreadyVerified,
	) {
		$this->superShare = $arguments['superShare'];
		$this->groupedShares = $arguments['groupedShares'];

		$absMountPoint = '/' . $user->getUID() . '/files/' . trim($this->superShare->getTarget(), '/') . '/';

		// after the mountpoint is verified for the first time, only new mountpoints (e.g. groupfolders can overwrite the target)
		if (!$alreadyVerified || isset($mountpoints[$absMountPoint])) {
			$newMountPoint = $this->verifyMountPoint($this->superShare, $mountpoints, $folderExistCache);
			$absMountPoint = '/' . $user->getUID() . '/files/' . trim($newMountPoint, '/') . '/';
		}

		parent::__construct($storage, $absMountPoint, $arguments, $loader, null, null, MountProvider::class);
	}

	/**
	 * check if the parent folder exists otherwise move the mount point up
	 *
	 * @param IShare $share
	 * @param SharedMount[] $mountpoints
	 * @param CappedMemoryCache<bool> $folderExistCache
	 * @return string
	 */
	private function verifyMountPoint(
		IShare $share,
		array $mountpoints,
		CappedMemoryCache $folderExistCache,
	) {
		$mountPoint = basename($share->getTarget());
		$parent = dirname($share->getTarget());

		$event = new VerifyMountPointEvent($share, $this->recipientView, $parent);
		$this->eventDispatcher->dispatchTyped($event);
		$parent = $event->getParent();

		$cached = $folderExistCache->get($parent);
		if ($cached) {
			$parentExists = $cached;
		} else {
			$parentExists = $this->recipientView->is_dir($parent);
			$folderExistCache->set($parent, $parentExists);
		}
		if (!$parentExists) {
			$parent = Helper::getShareFolder($this->recipientView, $this->user->getUID());
		}

		$newMountPoint = $this->generateUniqueTarget(
			Filesystem::normalizePath($parent . '/' . $mountPoint),
			$this->recipientView,
			$mountpoints
		);

		if ($newMountPoint !== $share->getTarget()) {
			$this->updateFileTarget($newMountPoint, $share);
		}

		return $newMountPoint;
	}

	/**
	 * update fileTarget in the database if the mount point changed
	 *
	 * @param string $newPath
	 * @param IShare $share
	 * @return bool
	 */
	private function updateFileTarget($newPath, &$share) {
		$share->setTarget($newPath);

		foreach ($this->groupedShares as $tmpShare) {
			$tmpShare->setTarget($newPath);
			Server::get(\OCP\Share\IManager::class)->moveShare($tmpShare, $this->user->getUID());
		}

		$this->eventDispatcher->dispatchTyped(new InvalidateMountCacheEvent($this->user));
	}


	/**
	 * @param string $path
	 * @param View $view
	 * @param SharedMount[] $mountpoints
	 * @return mixed
	 */
	private function generateUniqueTarget($path, $view, array $mountpoints) {
		$pathinfo = pathinfo($path);
		$ext = isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';
		$name = $pathinfo['filename'];
		$dir = $pathinfo['dirname'];

		$i = 2;
		$absolutePath = $this->recipientView->getAbsolutePath($path) . '/';
		while ($view->file_exists($path) || isset($mountpoints[$absolutePath])) {
			$path = Filesystem::normalizePath($dir . '/' . $name . ' (' . $i . ')' . $ext);
			$absolutePath = $this->recipientView->getAbsolutePath($path) . '/';
			$i++;
		}

		return $path;
	}

	/**
	 * Format a path to be relative to the /user/files/ directory
	 *
	 * @param string $path the absolute path
	 * @return string e.g. turns '/admin/files/test.txt' into '/test.txt'
	 * @throws BrokenPath
	 */
	protected function stripUserFilesPath($path) {
		$trimmed = ltrim($path, '/');
		$split = explode('/', $trimmed);

		// it is not a file relative to data/user/files
		if (count($split) < 3 || $split[1] !== 'files') {
			Server::get(LoggerInterface::class)->error('Can not strip userid and "files/" from path: ' . $path, ['app' => 'files_sharing']);
			throw new BrokenPath('Path does not start with /user/files', 10);
		}

		// skip 'user' and 'files'
		$sliced = array_slice($split, 2);
		$relPath = implode('/', $sliced);

		return '/' . $relPath;
	}

	/**
	 * Move the mount point to $target
	 *
	 * @param string $target the target mount point
	 * @return bool
	 */
	public function moveMount($target) {
		$relTargetPath = $this->stripUserFilesPath($target);
		$share = $this->storage->getShare();

		$result = true;

		try {
			$this->updateFileTarget($relTargetPath, $share);
			$this->setMountPoint($target);
			$this->storage->setMountPoint($relTargetPath);
		} catch (\Exception $e) {
			Server::get(LoggerInterface::class)->error(
				'Could not rename mount point for shared folder "' . $this->getMountPoint() . '" to "' . $target . '"',
				[
					'app' => 'files_sharing',
					'exception' => $e,
				]
			);
		}

		return $result;
	}

	/**
	 * Remove the mount points
	 *
	 * @return bool
	 */
	public function removeMount() {
		$mountManager = Filesystem::getMountManager();
		/** @var SharedStorage $storage */
		$storage = $this->getStorage();
		$result = $storage->unshareStorage();
		$mountManager->removeMount($this->mountPoint);

		return $result;
	}

	/**
	 * @return IShare
	 */
	public function getShare() {
		return $this->superShare;
	}

	/**
	 * @return IShare[]
	 */
	public function getGroupedShares(): array {
		return $this->groupedShares;
	}

	/**
	 * Get the file id of the root of the storage
	 *
	 * @return int
	 */
	public function getStorageRootId() {
		return $this->getShare()->getNodeId();
	}

	/**
	 * @return int
	 */
	public function getNumericStorageId() {
		if (!is_null($this->getShare()->getNodeCacheEntry())) {
			return $this->getShare()->getNodeCacheEntry()->getStorageId();
		} else {
			$builder = Server::get(IDBConnection::class)->getQueryBuilder();

			$query = $builder->select('storage')
				->from('filecache')
				->where($builder->expr()->eq('fileid', $builder->createNamedParameter($this->getStorageRootId())));

			$result = $query->executeQuery();
			$row = $result->fetch();
			$result->closeCursor();
			if ($row) {
				return (int)$row['storage'];
			}
			return -1;
		}
	}

	public function getMountType() {
		return 'shared';
	}
}
