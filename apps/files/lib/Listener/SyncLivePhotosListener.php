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

use OCA\Files\Service\LivePhotosService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Cache\CacheEntryRemovedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\FilesMetadata\IFilesMetadataManager;

/**
 * @template-implements IEventListener<Event>
 */
class SyncLivePhotosListener implements IEventListener {
	/** @var Array<int, string> */
	private array $pendingRenames = [];
	/** @var Array<int, bool> */
	private array $pendingDeletion = [];

	public function __construct(
		private ?Folder $userFolder,
		private IFilesMetadataManager $filesMetadataManager,
		private LivePhotosService $livePhotosService,
	) {
	}

	public function handle(Event $event): void {
		if ($this->userFolder === null) {
			return;
		}

		$peerFileId = null;

		if ($event instanceof BeforeNodeRenamedEvent) {
			$peerFileId = $this->livePhotosService->getLivePhotoPeerId($event->getSource()->getId());
		} elseif ($event instanceof BeforeNodeDeletedEvent) {
			$peerFileId = $this->livePhotosService->getLivePhotoPeerId($event->getNode()->getId());
		} elseif ($event instanceof CacheEntryRemovedEvent) {
			$peerFileId = $this->livePhotosService->getLivePhotoPeerId($event->getFileId());
		}

		if ($peerFileId === null) {
			return; // Not a live photo.
		}

		// Check the user's folder.
		$peerFile = $this->userFolder->getById($peerFileId)[0];

		if ($peerFile === null) {
			return; // Peer file not found.
		}

		if ($event instanceof BeforeNodeRenamedEvent) {
			$this->handleMove($event, $peerFile);
		} elseif ($event instanceof BeforeNodeDeletedEvent) {
			$this->handleDeletion($event, $peerFile);
		} elseif ($event instanceof CacheEntryRemovedEvent) {
			$peerFile->delete();
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
}
