<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Listener;

use OCA\Files\Service\LivePhotosService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Exceptions\AbortedEventException;
use OCP\Files\Cache\CacheEntryRemovedEvent;
use OCP\Files\Events\Node\AbstractNodesEvent;
use OCP\Files\Events\Node\BeforeNodeCopiedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\FilesMetadata\IFilesMetadataManager;

/**
 * @template-implements IEventListener<Event>
 */
class SyncLivePhotosListener implements IEventListener {
	/** @var Array<int> */
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
		} elseif ($event instanceof BeforeNodeCopiedEvent || $event instanceof NodeCopiedEvent) {
			$peerFileId = $this->livePhotosService->getLivePhotoPeerId($event->getSource()->getId());
		}

		if ($peerFileId === null) {
			return; // Not a live photo.
		}

		// Check the user's folder.
		$peerFile = $this->userFolder->getFirstNodeById($peerFileId);

		if ($peerFile === null) {
			return; // Peer file not found.
		}

		if ($event instanceof BeforeNodeRenamedEvent) {
			$this->handleMove($event, $peerFile, false);
		} elseif ($event instanceof BeforeNodeDeletedEvent) {
			$this->handleDeletion($event, $peerFile);
		} elseif ($event instanceof CacheEntryRemovedEvent) {
			$peerFile->delete();
		} elseif ($event instanceof BeforeNodeCopiedEvent) {
			$this->handleMove($event, $peerFile, true);
		} elseif ($event instanceof NodeCopiedEvent) {
			$this->handleCopy($event, $peerFile);
		}
	}

	/**
	 * During rename events, which also include move operations,
	 * we rename the peer file using the same name.
	 * The event listener being singleton, we can store the current state
	 * of pending renames inside the 'pendingRenames' property,
	 * to prevent infinite recursive.
	 */
	private function handleMove(AbstractNodesEvent $event, Node $peerFile, bool $prepForCopyOnly = false): void {
		if (!($event instanceof BeforeNodeCopiedEvent) &&
			!($event instanceof BeforeNodeRenamedEvent)) {
			return;
		}

		$sourceFile = $event->getSource();
		$targetFile = $event->getTarget();
		$targetParent = $targetFile->getParent();
		$sourceExtension = $sourceFile->getExtension();
		$peerFileExtension = $peerFile->getExtension();
		$targetName = $targetFile->getName();

		if (!str_ends_with($targetName, "." . $sourceExtension)) {
			throw new AbortedEventException('Cannot change the extension of a Live Photo');
		}

		try {
			$targetParent->get($targetName);
			throw new AbortedEventException('A file already exist at destination path of the Live Photo');
		} catch (NotFoundException) {
		}

		$peerTargetName = substr($targetName, 0, -strlen($sourceExtension)) . $peerFileExtension;
		try {
			$targetParent->get($peerTargetName);
			throw new AbortedEventException('A file already exist at destination path of the Live Photo');
		} catch (NotFoundException) {
		}

		// in case the rename was initiated from this listener, we stop right now
		if ($prepForCopyOnly || in_array($peerFile->getId(), $this->pendingRenames)) {
			return;
		}

		$this->pendingRenames[] = $sourceFile->getId();
		try {
			$peerFile->move($targetParent->getPath() . '/' . $peerTargetName);
		} catch (\Throwable $ex) {
			throw new AbortedEventException($ex->getMessage());
		}

		array_diff($this->pendingRenames, [$sourceFile->getId()]);
	}


	/**
	 * handle copy, we already know if it is doable from BeforeNodeCopiedEvent, so we just copy the linked file
	 *
	 * @param NodeCopiedEvent $event
	 * @param Node $peerFile
	 */
	private function handleCopy(NodeCopiedEvent $event, Node $peerFile): void {
		$sourceFile = $event->getSource();
		$sourceExtension = $sourceFile->getExtension();
		$peerFileExtension = $peerFile->getExtension();
		$targetFile = $event->getTarget();
		$targetParent = $targetFile->getParent();
		$targetName = $targetFile->getName();
		$peerTargetName = substr($targetName, 0, -strlen($sourceExtension)) . $peerFileExtension;

		/**
		 * let's use freshly set variable.
		 * we copy the file and get its id. We already have the id of the current copy
		 * We have everything to update metadata and keep the link between the 2 copies.
		 */
		$newPeerFile = $peerFile->copy($targetParent->getPath() . '/' . $peerTargetName);
		$targetMetadata = $this->filesMetadataManager->getMetadata($targetFile->getId(), true);
		$targetMetadata->setString('files-live-photo', (string)$newPeerFile->getId());
		$this->filesMetadataManager->saveMetadata($targetMetadata);
		$peerMetadata = $this->filesMetadataManager->getMetadata($newPeerFile->getId(), true);
		$peerMetadata->setString('files-live-photo', (string)$targetFile->getId());
		$this->filesMetadataManager->saveMetadata($peerMetadata);
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
				throw new AbortedEventException("Cannot delete the video part of a live photo");
			}
		} else {
			$this->pendingDeletion[$deletedFile->getId()] = true;
			try {
				$peerFile->delete();
			} catch (\Throwable $ex) {
				throw new AbortedEventException($ex->getMessage());
			}
		}
		return;
	}
}
