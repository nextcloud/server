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
use OCA\Files_Sharing\Exceptions\BrokenPath;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\InvalidateMountCacheEvent;
use OCP\Files\Storage\IStorageFactory;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
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
		$arguments,
		IStorageFactory $loader,
		private IEventDispatcher $eventDispatcher,
		private IUser $user,
	) {
		$this->superShare = $arguments['superShare'];
		$this->groupedShares = $arguments['groupedShares'];

		$absMountPoint = '/' . $user->getUID() . '/files/' . trim($this->superShare->getTarget(), '/') . '/';

		parent::__construct($storage, $absMountPoint, $arguments, $loader, null, null, MountProvider::class);
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
			$row = $result->fetchAssociative();
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

	public function getUser(): IUser {
		return $this->user;
	}
}
