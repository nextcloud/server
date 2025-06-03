<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Listener;

use Exception;
use OC\Files\Node\NonExistingFile;
use OC\Files\Node\NonExistingFolder;
use OC\Files\View;
use OC\FilesMetadata\Model\FilesMetadata;
use OCA\Files\Service\LivePhotosService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Exceptions\AbortedEventException;
use OCP\Files\Cache\CacheEntryRemovedEvent;
use OCP\Files\Events\Node\BeforeNodeCopiedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
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
	/** @var Array<int> */
	private array $pendingCopies = [];

	public function __construct(
		private ?Folder $userFolder,
		private IFilesMetadataManager $filesMetadataManager,
		private LivePhotosService $livePhotosService,
		private IRootFolder $rootFolder,
		private View $view,
	) {
	}

	public function handle(Event $event): void {
		if ($this->userFolder === null) {
			return;
		}

		if ($event instanceof BeforeNodeCopiedEvent || $event instanceof NodeCopiedEvent) {
			$this->handleCopyRecursive($event, $event->getSource(), $event->getTarget());
		} else {
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
			$peerFile = $this->userFolder->getFirstNodeById($peerFileId);

			if ($peerFile === null) {
				return; // Peer file not found.
			}

			if ($event instanceof BeforeNodeRenamedEvent) {
				$this->runMoveOrCopyChecks($event->getSource(), $event->getTarget(), $peerFile);
				$this->handleMove($event->getSource(), $event->getTarget(), $peerFile);
			} elseif ($event instanceof BeforeNodeDeletedEvent) {
				$this->handleDeletion($event, $peerFile);
			} elseif ($event instanceof CacheEntryRemovedEvent) {
				$peerFile->delete();
			}
		}
	}

	private function runMoveOrCopyChecks(Node $sourceFile, Node $targetFile, Node $peerFile): void {
		$targetParent = $targetFile->getParent();
		$sourceExtension = $sourceFile->getExtension();
		$peerFileExtension = $peerFile->getExtension();
		$targetName = $targetFile->getName();
		$peerTargetName = substr($targetName, 0, -strlen($sourceExtension)) . $peerFileExtension;

		if (!str_ends_with($targetName, '.' . $sourceExtension)) {
			throw new AbortedEventException('Cannot change the extension of a Live Photo');
		}

		try {
			$targetParent->get($targetName);
			throw new AbortedEventException('A file already exist at destination path of the Live Photo');
		} catch (NotFoundException) {
		}

		if (!($targetParent instanceof NonExistingFolder)) {
			try {
				$targetParent->get($peerTargetName);
				throw new AbortedEventException('A file already exist at destination path of the Live Photo');
			} catch (NotFoundException) {
			}
		}
	}

	/**
	 * During rename events, which also include move operations,
	 * we rename the peer file using the same name.
	 * The event listener being singleton, we can store the current state
	 * of pending renames inside the 'pendingRenames' property,
	 * to prevent infinite recursive.
	 */
	private function handleMove(Node $sourceFile, Node $targetFile, Node $peerFile): void {
		$targetParent = $targetFile->getParent();
		$sourceExtension = $sourceFile->getExtension();
		$peerFileExtension = $peerFile->getExtension();
		$targetName = $targetFile->getName();
		$peerTargetName = substr($targetName, 0, -strlen($sourceExtension)) . $peerFileExtension;

		// in case the rename was initiated from this listener, we stop right now
		if (in_array($peerFile->getId(), $this->pendingRenames)) {
			return;
		}

		$this->pendingRenames[] = $sourceFile->getId();
		try {
			$peerFile->move($targetParent->getPath() . '/' . $peerTargetName);
		} catch (\Throwable $ex) {
			throw new AbortedEventException($ex->getMessage());
		}

		$this->pendingRenames = array_diff($this->pendingRenames, [$sourceFile->getId()]);
	}


	/**
	 * handle copy, we already know if it is doable from BeforeNodeCopiedEvent, so we just copy the linked file
	 */
	private function handleCopy(File $sourceFile, File $targetFile, File $peerFile): void {
		$sourceExtension = $sourceFile->getExtension();
		$peerFileExtension = $peerFile->getExtension();
		$targetParent = $targetFile->getParent();
		$targetName = $targetFile->getName();
		$peerTargetName = substr($targetName, 0, -strlen($sourceExtension)) . $peerFileExtension;

		if ($targetParent->nodeExists($peerTargetName)) {
			// If the copy was a folder copy, then the peer file already exists.
			$targetPeerFile = $targetParent->get($peerTargetName);
		} else {
			// If the copy was a file copy, then we need to create the peer file.
			$targetPeerFile = $peerFile->copy($targetParent->getPath() . '/' . $peerTargetName);
		}

		/** @var FilesMetadata $targetMetadata */
		$targetMetadata = $this->filesMetadataManager->getMetadata($targetFile->getId(), true);
		$targetMetadata->setStorageId($targetFile->getStorage()->getCache()->getNumericStorageId());
		$targetMetadata->setString('files-live-photo', (string)$targetPeerFile->getId());
		$this->filesMetadataManager->saveMetadata($targetMetadata);
		/** @var FilesMetadata $peerMetadata */
		$peerMetadata = $this->filesMetadataManager->getMetadata($targetPeerFile->getId(), true);
		$peerMetadata->setStorageId($targetPeerFile->getStorage()->getCache()->getNumericStorageId());
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
				throw new AbortedEventException('Cannot delete the video part of a live photo');
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

	/*
	 * Recursively get all the peer ids of a live photo.
	 * Needed when coping a folder.
	 *
	 * @param BeforeNodeCopiedEvent|NodeCopiedEvent $event
	 */
	private function handleCopyRecursive(Event $event, Node $sourceNode, Node $targetNode): void {
		if ($sourceNode instanceof Folder && $targetNode instanceof Folder) {
			foreach ($sourceNode->getDirectoryListing() as $sourceChild) {
				if ($event instanceof BeforeNodeCopiedEvent) {
					if ($sourceChild instanceof Folder) {
						$targetChild = new NonExistingFolder($this->rootFolder, $this->view, $targetNode->getPath() . '/' . $sourceChild->getName(), null, $targetNode);
					} else {
						$targetChild = new NonExistingFile($this->rootFolder, $this->view, $targetNode->getPath() . '/' . $sourceChild->getName(), null, $targetNode);
					}
				} elseif ($event instanceof NodeCopiedEvent) {
					$targetChild = $targetNode->get($sourceChild->getName());
				} else {
					throw new Exception('Event is type is not supported');
				}

				$this->handleCopyRecursive($event, $sourceChild, $targetChild);
			}
		} elseif ($sourceNode instanceof File && $targetNode instanceof File) {
			// in case the copy was initiated from this listener, we stop right now
			if (in_array($sourceNode->getId(), $this->pendingCopies)) {
				return;
			}

			$peerFileId = $this->livePhotosService->getLivePhotoPeerId($sourceNode->getId());
			if ($peerFileId === null) {
				return;
			}
			$peerFile = $this->userFolder->getFirstNodeById($peerFileId);
			if ($peerFile === null) {
				return;
			}

			$this->pendingCopies[] = $peerFileId;
			if ($event instanceof BeforeNodeCopiedEvent) {
				$this->runMoveOrCopyChecks($sourceNode, $targetNode, $peerFile);
			} elseif ($event instanceof NodeCopiedEvent) {
				$this->handleCopy($sourceNode, $targetNode, $peerFile);
			}
			$this->pendingCopies = array_diff($this->pendingCopies, [$peerFileId]);
		} else {
			throw new Exception('Source and target type are not matching');
		}
	}
}
