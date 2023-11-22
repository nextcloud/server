<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\Files_Trashbin;

use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\Wrapper;
use OCA\Files_Trashbin\Events\MoveToTrashEvent;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\Storage\IStorage;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class Storage extends Wrapper {
	private string $mountPoint;
	private IUserManager$userManager;
	private LoggerInterface $logger;
	private IEventDispatcher $eventDispatcher;
	private IRootFolder $rootFolder;
	private ITrashManager $trashManager;
	private bool $trashEnabled = true;

	/**
	 * Storage constructor.
	 *
	 * @param array $parameters
	 * @param ITrashManager|null $trashManager
	 * @param IUserManager|null $userManager
	 * @param LoggerInterface|null $logger
	 * @param IEventDispatcher|null $eventDispatcher
	 * @param IRootFolder|null $rootFolder
	 */
	public function __construct(
		$parameters,
		ITrashManager $trashManager = null,
		IUserManager $userManager = null,
		LoggerInterface $logger = null,
		IEventDispatcher $eventDispatcher = null,
		IRootFolder $rootFolder = null
	) {
		$this->mountPoint = $parameters['mountPoint'];
		$this->trashManager = $trashManager;
		$this->userManager = $userManager;
		$this->logger = $logger;
		$this->eventDispatcher = $eventDispatcher;
		$this->rootFolder = $rootFolder;
		parent::__construct($parameters);
	}

	/**
	 * Deletes the given file by moving it into the trashbin.
	 *
	 * @param string $path path of file or folder to delete
	 *
	 * @return bool true if the operation succeeded, false otherwise
	 */
	public function unlink($path) {
		if ($this->trashEnabled) {
			try {
				return $this->doDelete($path, 'unlink');
			} catch (GenericEncryptionException $e) {
				// in case of a encryption exception we delete the file right away
				$this->logger->info(
					"Can't move file " . $path .
					" to the trash bin, therefore it was deleted right away");

				return $this->storage->unlink($path);
			}
		} else {
			return $this->storage->unlink($path);
		}
	}

	/**
	 * Deletes the given folder by moving it into the trashbin.
	 *
	 * @param string $path path of folder to delete
	 *
	 * @return bool true if the operation succeeded, false otherwise
	 */
	public function rmdir($path) {
		if ($this->trashEnabled) {
			return $this->doDelete($path, 'rmdir');
		} else {
			return $this->storage->rmdir($path);
		}
	}

	/**
	 * check if it is a file located in data/user/files only files in the
	 * 'files' directory should be moved to the trash
	 *
	 * @param $path
	 * @return bool
	 */
	protected function shouldMoveToTrash($path) {
		$normalized = Filesystem::normalizePath($this->mountPoint . '/' . $path);
		$parts = explode('/', $normalized);
		if (count($parts) < 4 || strpos($normalized, '/appdata_') === 0) {
			return false;
		}

		// check if there is a app which want to disable the trash bin for this file
		$fileId = $this->storage->getCache()->getId($path);
		$owner = $this->storage->getOwner($path);
		if ($owner === false || $this->storage->instanceOfStorage(\OCA\Files_Sharing\External\Storage::class)) {
			$nodes = $this->rootFolder->getById($fileId);
		} else {
			$nodes = $this->rootFolder->getUserFolder($owner)->getById($fileId);
		}

		foreach ($nodes as $node) {
			$event = $this->createMoveToTrashEvent($node);
			$this->eventDispatcher->dispatchTyped($event);
			$this->eventDispatcher->dispatch('OCA\Files_Trashbin::moveToTrash', $event);
			if ($event->shouldMoveToTrashBin() === false) {
				return false;
			}
		}

		if ($parts[2] === 'files' && $this->userManager->userExists($parts[1])) {
			return true;
		}

		return false;
	}

	/**
	 * get move to trash event
	 *
	 * @param Node $node
	 * @return MoveToTrashEvent
	 */
	protected function createMoveToTrashEvent(Node $node) {
		return new MoveToTrashEvent($node);
	}

	/**
	 * Run the delete operation with the given method
	 *
	 * @param string $path path of file or folder to delete
	 * @param string $method either "unlink" or "rmdir"
	 *
	 * @return bool true if the operation succeeded, false otherwise
	 */
	private function doDelete($path, $method) {
		if (
			!\OC::$server->getAppManager()->isEnabledForUser('files_trashbin')
			|| (pathinfo($path, PATHINFO_EXTENSION) === 'part')
			|| $this->shouldMoveToTrash($path) === false
		) {
			return call_user_func([$this->storage, $method], $path);
		}

		// check permissions before we continue, this is especially important for
		// shared files
		if (!$this->isDeletable($path)) {
			return false;
		}

		$isMovedToTrash = $this->trashManager->moveToTrash($this, $path);
		if (!$isMovedToTrash) {
			return call_user_func([$this->storage, $method], $path);
		} else {
			return true;
		}
	}

	/**
	 * Setup the storage wrapper callback
	 */
	public static function setupStorage() {
		$trashManager = \OC::$server->get(ITrashManager::class);
		$userManager = \OC::$server->get(IUserManager::class);
		$logger = \OC::$server->get(LoggerInterface::class);
		$eventDispatcher = \OC::$server->get(IEventDispatcher::class);
		$rootFolder = \OC::$server->get(IRootFolder::class);
		Filesystem::addStorageWrapper(
			'oc_trashbin',
			function (string $mountPoint, IStorage $storage) use ($trashManager, $userManager, $logger, $eventDispatcher, $rootFolder) {
				return new Storage(
					['storage' => $storage, 'mountPoint' => $mountPoint],
					$trashManager,
					$userManager,
					$logger,
					$eventDispatcher,
					$rootFolder,
				);
			},
			1);
	}

	public function getMountPoint() {
		return $this->mountPoint;
	}

	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$sourceIsTrashbin = $sourceStorage->instanceOfStorage(Storage::class);
		try {
			// the fallback for moving between storage involves a copy+delete
			// we don't want to trigger the trashbin when doing the delete
			if ($sourceIsTrashbin) {
				/** @var Storage $sourceStorage */
				$sourceStorage->disableTrash();
			}
			$result = parent::moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
			if ($sourceIsTrashbin) {
				/** @var Storage $sourceStorage */
				$sourceStorage->enableTrash();
			}
			return $result;
		} catch (\Exception $e) {
			if ($sourceIsTrashbin) {
				/** @var Storage $sourceStorage */
				$sourceStorage->enableTrash();
			}
			throw $e;
		}
	}

	protected function disableTrash() {
		$this->trashEnabled = false;
	}

	protected function enableTrash() {
		$this->trashEnabled = true;
	}
}
