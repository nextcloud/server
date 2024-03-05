<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Louis Chemineau <louis@chmn.me>
 *
 * @author Louis Chemineau <louis@chmn.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Files\Listener;

use OCA\Files_Trashbin\Events\BeforeNodeRestoredEvent;
use OCA\Files_Trashbin\Trash\ITrashItem;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Cache\CacheEntryRemovedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IUserSession;

/**
 * @template-implements IEventListener<Event>
 */
class SyncLivePhotosListener implements IEventListener {
	/** @var Array<int, string> */
	private array $pendingRenames = [];
	/** @var Array<int, bool> */
	private array $pendingDeletion = [];
	/** @var Array<int, bool> */
	private array $pendingRestores = [];

	public function __construct(
		private ?Folder $userFolder,
		private ?IUserSession $userSession,
		private ITrashManager $trashManager,
		private IFilesMetadataManager $filesMetadataManager,
	) {
	}

	public function handle(Event $event): void {
		if ($this->userFolder === null || $this->userSession === null) {
			return;
		}

		$peerFile = null;

		if ($event instanceof BeforeNodeRenamedEvent) {
			$peerFile = $this->getLivePhotoPeer($event->getSource()->getId());
		} elseif ($event instanceof BeforeNodeRestoredEvent) {
			$peerFile = $this->getLivePhotoPeer($event->getSource()->getId());
		} elseif ($event instanceof BeforeNodeDeletedEvent) {
			$peerFile = $this->getLivePhotoPeer($event->getNode()->getId());
		} elseif ($event instanceof CacheEntryRemovedEvent) {
			$peerFile = $this->getLivePhotoPeer($event->getFileId());
		}

		if ($peerFile === null) {
			return; // not a Live Photo
		}

		if ($event instanceof BeforeNodeRenamedEvent) {
			$this->handleMove($event, $peerFile);
		} elseif ($event instanceof BeforeNodeDeletedEvent) {
			$this->handleDeletion($event, $peerFile);
		} elseif ($event instanceof CacheEntryRemovedEvent) {
			$peerFile->delete();
		} elseif ($event instanceof BeforeNodeRestoredEvent) {
			$this->handleRestore($event, $peerFile);
		}
	}

	/**
	 * During rename events, which also include move operations,
	 * we rename the peer file using the same name.
	 * The event listener being singleton, we can store the current state
	 * of pending renames inside the 'pendingRenames' property,
	 * to prevent infinite recursive.
	 */
	private function handleMove(BeforeNodeRenamedEvent $event, Node $peerFile): void {
		$sourceFile = $event->getSource();
		$targetFile = $event->getTarget();
		$targetParent = $targetFile->getParent();
		$sourceExtension = $sourceFile->getExtension();
		$peerFileExtension = $peerFile->getExtension();
		$targetName = $targetFile->getName();
		$targetPath = $targetFile->getPath();

		if (!str_ends_with($targetName, ".".$sourceExtension)) {
			$event->abortOperation(new NotPermittedException("Cannot change the extension of a Live Photo"));
		}

		try {
			$targetParent->get($targetName);
			$event->abortOperation(new NotPermittedException("A file already exist at destination path of the Live Photo"));
		} catch (NotFoundException $ex) {
		}

		$peerTargetName = substr($targetName, 0, -strlen($sourceExtension)) . $peerFileExtension;
		try {
			$targetParent->get($peerTargetName);
			$event->abortOperation(new NotPermittedException("A file already exist at destination path of the Live Photo"));
		} catch (NotFoundException $ex) {
		}

		// in case the rename was initiated from this listener, we stop right now
		if (array_key_exists($peerFile->getId(), $this->pendingRenames)) {
			return;
		}

		$this->pendingRenames[$sourceFile->getId()] = $targetPath;
		try {
			$peerFile->move($targetParent->getPath() . '/' . $peerTargetName);
		} catch (\Throwable $ex) {
			$event->abortOperation($ex);
		}
		unset($this->pendingRenames[$sourceFile->getId()]);
	}

	/**
	 * During deletion event, we trigger another recursive delete on the peer file.
	 * Delete operations on the .mov file directly are currently blocked.
	 * The event listener being singleton, we can store the current state
	 * of pending deletions inside the 'pendingDeletions' property,
	 * to prevent infinite recursivity.
	 */
	private function handleDeletion(BeforeNodeDeletedEvent $event, Node $peerFile): void {
		$deletedFile = $event->getNode();
		if ($deletedFile->getMimetype() === 'video/quicktime') {
			if (isset($this->pendingDeletion[$peerFile->getId()])) {
				unset($this->pendingDeletion[$peerFile->getId()]);
				return;
			} else {
				$event->abortOperation(new NotPermittedException("Cannot delete the video part of a live photo"));
			}
		} else {
			$this->pendingDeletion[$deletedFile->getId()] = true;
			try {
				$peerFile->delete();
			} catch (\Throwable $ex) {
				$event->abortOperation($ex);
			}
		}
		return;
	}

	/**
	 * During restore event, we trigger another recursive restore on the peer file.
	 * Restore operations on the .mov file directly are currently blocked.
	 * The event listener being singleton, we can store the current state
	 * of pending restores inside the 'pendingRestores' property,
	 * to prevent infinite recursivity.
	 */
	private function handleRestore(BeforeNodeRestoredEvent $event, Node $peerFile): void {
		$sourceFile = $event->getSource();

		if ($sourceFile->getMimetype() === 'video/quicktime') {
			if (isset($this->pendingRestores[$peerFile->getId()])) {
				unset($this->pendingRestores[$peerFile->getId()]);
				return;
			} else {
				$event->abortOperation(new NotPermittedException("Cannot restore the video part of a live photo"));
			}
		} else {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return;
			}

			$peerTrashItem = $this->trashManager->getTrashNodeById($user, $peerFile->getId());
			// Peer file is not in the bin, no need to restore it.
			if ($peerTrashItem === null) {
				return;
			}

			$trashRoot = $this->trashManager->listTrashRoot($user);
			$trashItem = $this->getTrashItem($trashRoot, $peerFile->getInternalPath());

			if ($trashItem === null) {
				$event->abortOperation(new NotFoundException("Couldn't find peer file in trashbin"));
			}

			$this->pendingRestores[$sourceFile->getId()] = true;
			try {
				$this->trashManager->restoreItem($trashItem);
			} catch (\Throwable $ex) {
				$event->abortOperation($ex);
			}
		}
	}

	/**
	 * Helper method to get the associated live photo file.
	 * We first look for it in the user folder, and if we
	 * cannot find it here, we look for it in the user's trashbin.
	 */
	private function getLivePhotoPeer(int $nodeId): ?Node {
		if ($this->userFolder === null || $this->userSession === null) {
			return null;
		}

		try {
			$metadata = $this->filesMetadataManager->getMetadata($nodeId);
		} catch (FilesMetadataNotFoundException $ex) {
			return null;
		}

		if (!$metadata->hasKey('files-live-photo')) {
			return null;
		}

		$peerFileId = (int)$metadata->getString('files-live-photo');

		// Check the user's folder.
		$node = $this->userFolder->getFirstNodeById($peerFileId);
		if ($node) {
			return $node;
		}

		// Check the user's trashbin.
		$user = $this->userSession->getUser();
		if ($user !== null) {
			$peerFile = $this->trashManager->getTrashNodeById($user, $peerFileId);
			if ($peerFile !== null) {
				return $peerFile;
			}
		}

		$metadata->unset('files-live-photo');
		return null;
	}

	/**
	 * There is currently no method to restore a file based on its fileId or path.
	 * So we have to manually find a ITrashItem from the trash item list.
	 * TODO: This should be replaced by a proper method in the TrashManager.
	 */
	private function getTrashItem(array $trashFolder, string $path): ?ITrashItem {
		foreach($trashFolder as $trashItem) {
			if (str_starts_with($path, "files_trashbin/files".$trashItem->getTrashPath())) {
				if ($path === "files_trashbin/files".$trashItem->getTrashPath()) {
					return $trashItem;
				}

				if ($trashItem instanceof Folder) {
					$node = $this->getTrashItem($trashItem->getDirectoryListing(), $path);
					if ($node !== null) {
						return $node;
					}
				}
			}
		}

		return null;
	}
}
