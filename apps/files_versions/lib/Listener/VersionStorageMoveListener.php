<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Versions\Listener;

use Exception;
use OC\Files\Node\NonExistingFile;
use OC\Files\Node\NonExistingFolder;
use OCA\Files_Versions\Versions\IVersionBackend;
use OCA\Files_Versions\Versions\IVersionManager;
use OCA\Files_Versions\Versions\IVersionsImporterBackend;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\AbstractNodesEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\IUserSession;

/** @template-implements IEventListener<Event> */
class VersionStorageMoveListener implements IEventListener {
	/** @var File[] */
	private array $movedNodes = [];

	public function __construct(
		private IVersionManager $versionManager,
		private IUserSession $userSession,
	) {
	}

	/**
	 * @abstract Moves version across storages if necessary.
	 * @throws Exception No user in session
	 */
	public function handle(Event $event): void {
		if (!($event instanceof AbstractNodesEvent)) {
			return;
		}

		$source = $event->getSource();
		$target = $event->getTarget();

		$sourceStorage = $this->getNodeStorage($source);
		$targetStorage = $this->getNodeStorage($target);

		$sourceBackend = $this->versionManager->getBackendForStorage($sourceStorage);
		$targetBackend = $this->versionManager->getBackendForStorage($targetStorage);

		// If same backend, nothing to do.
		if ($sourceBackend === $targetBackend) {
			return;
		}

		$user = $this->userSession->getUser() ?? $source->getOwner();

		if ($user === null) {
			throw new Exception('Cannot move versions across storages without a user.');
		}

		if ($event instanceof BeforeNodeRenamedEvent) {
			$this->recursivelyPrepareMove($source);
		} elseif ($event instanceof NodeRenamedEvent || $event instanceof NodeCopiedEvent) {
			$this->recursivelyHandleMoveOrCopy($event, $user, $source, $target, $sourceBackend, $targetBackend);
		}
	}

	/**
	 * Store all sub files in this->movedNodes so their info can be used after the operation.
	 */
	private function recursivelyPrepareMove(Node $source): void {
		if ($source instanceof File) {
			$this->movedNodes[$source->getId()] = $source;
		} elseif ($source instanceof Folder) {
			foreach ($source->getDirectoryListing() as $child) {
				$this->recursivelyPrepareMove($child);
			}
		}
	}

	/**
	 * Call handleMoveOrCopy on each sub files
	 * @param NodeRenamedEvent|NodeCopiedEvent $event
	 */
	private function recursivelyHandleMoveOrCopy(Event $event, IUser $user, ?Node $source, Node $target, IVersionBackend $sourceBackend, IVersionBackend $targetBackend): void {
		if ($target instanceof File) {
			if ($event instanceof NodeRenamedEvent) {
				$source = $this->movedNodes[$target->getId()];
			}

			/** @var File $source */
			$this->handleMoveOrCopy($event, $user, $source, $target, $sourceBackend, $targetBackend);
		} elseif ($target instanceof Folder) {
			/** @var Folder $source */
			foreach ($target->getDirectoryListing() as $targetChild) {
				if ($event instanceof NodeCopiedEvent) {
					$sourceChild = $source->get($targetChild->getName());
				} else {
					$sourceChild = null;
				}

				$this->recursivelyHandleMoveOrCopy($event, $user, $sourceChild, $targetChild, $sourceBackend, $targetBackend);
			}
		}
	}

	/**
	 * Called only during NodeRenamedEvent or NodeCopiedEvent
	 * Will send the source node versions to the new backend, and then delete them from the old backend.
	 * @param NodeRenamedEvent|NodeCopiedEvent $event
	 */
	private function handleMoveOrCopy(Event $event, IUser $user, File $source, File $target, IVersionBackend $sourceBackend, IVersionBackend $targetBackend): void {
		if ($targetBackend instanceof IVersionsImporterBackend) {
			$versions = $sourceBackend->getVersionsForFile($user, $source);
			$targetBackend->importVersionsForFile($user, $source, $target, $versions);
		}

		if ($event instanceof NodeRenamedEvent && $sourceBackend instanceof IVersionsImporterBackend) {
			$sourceBackend->clearVersionsForFile($user, $source, $target);
		}
	}

	private function getNodeStorage(Node $node): IStorage {
		if ($node instanceof NonExistingFile || $node instanceof NonExistingFolder) {
			return $node->getParent()->getStorage();
		} else {
			return $node->getStorage();
		}
	}
}
