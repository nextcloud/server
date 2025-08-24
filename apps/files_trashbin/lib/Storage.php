<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin;

use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\Wrapper;
use OCA\Files_Trashbin\Events\MoveToTrashEvent;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\App\IAppManager;
use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\Storage\IStorage;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class Storage extends Wrapper {
	private string $mountPoint;
	private bool $trashEnabled = true;

	/**
	 * Storage constructor.
	 * @param array $parameters
	 */
	public function __construct(
		$parameters,
		private ?ITrashManager $trashManager = null,
		private ?IUserManager $userManager = null,
		private ?LoggerInterface $logger = null,
		private ?IEventDispatcher $eventDispatcher = null,
		private ?IRootFolder $rootFolder = null,
		private ?IRequest $request = null,
	) {
		$this->mountPoint = $parameters['mountPoint'];
		parent::__construct($parameters);
	}

	public function unlink(string $path): bool {
		if ($this->trashEnabled) {
			try {
				return $this->doDelete($path, 'unlink');
			} catch (GenericEncryptionException $e) {
				// in case of a encryption exception we delete the file right away
				$this->logger->info(
					"Can't move file " . $path
					. ' to the trash bin, therefore it was deleted right away');

				return $this->storage->unlink($path);
			}
		} else {
			return $this->storage->unlink($path);
		}
	}

	public function rmdir(string $path): bool {
		if ($this->trashEnabled) {
			return $this->doDelete($path, 'rmdir');
		} else {
			return $this->storage->rmdir($path);
		}
	}

	/**
	 * check if it is a file located in data/user/files only files in the
	 * 'files' directory should be moved to the trash
	 */
	protected function shouldMoveToTrash(string $path): bool {
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
	protected function createMoveToTrashEvent(Node $node): MoveToTrashEvent {
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
	private function doDelete(string $path, string $method): bool {
		$isTrashbinEnabled = Server::get(IAppManager::class)->isEnabledForUser('files_trashbin');
		$isPartFile = pathinfo($path, PATHINFO_EXTENSION) === 'part';
		$isSkipTrashHeaderSet = $this->request !== null && $this->request->getHeader('X-NC-Skip-Trashbin') === 'true';
		// We keep the shouldMoveToTrash call at the end to prevent emitting unnecessary event.
		$shouldMoveToTrash = $isTrashbinEnabled && !$isPartFile && !$isSkipTrashHeaderSet && $this->shouldMoveToTrash($path);

		if ($shouldMoveToTrash) {
			// check permissions before we continue, this is especially important for
			// shared files
			if (!$this->isDeletable($path)) {
				return false;
			}

			$isMovedToTrash = $this->trashManager->moveToTrash($this, $path);
			if ($isMovedToTrash) {
				return true;
			}
		}

		return call_user_func([$this->storage, $method], $path);
	}

	/**
	 * Setup the storage wrapper callback
	 */
	public static function setupStorage(): void {
		$trashManager = Server::get(ITrashManager::class);
		$userManager = Server::get(IUserManager::class);
		$logger = Server::get(LoggerInterface::class);
		$eventDispatcher = Server::get(IEventDispatcher::class);
		$rootFolder = Server::get(IRootFolder::class);
		$request = Server::get(IRequest::class);
		Filesystem::addStorageWrapper(
			'oc_trashbin',
			function (string $mountPoint, IStorage $storage) use ($trashManager, $userManager, $logger, $eventDispatcher, $rootFolder, $request) {
				return new Storage(
					['storage' => $storage, 'mountPoint' => $mountPoint],
					$trashManager,
					$userManager,
					$logger,
					$eventDispatcher,
					$rootFolder,
					$request,
				);
			},
			1);
	}

	public function getMountPoint() {
		return $this->mountPoint;
	}

	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
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

	protected function disableTrash(): void {
		$this->trashEnabled = false;
	}

	protected function enableTrash(): void {
		$this->trashEnabled = true;
	}
}
