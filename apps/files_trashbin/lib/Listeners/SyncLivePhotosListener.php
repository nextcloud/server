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

namespace OCA\Files_Trashbin\Listeners;

use OCA\Files\Service\LivePhotosService;
use OCA\Files_Trashbin\Events\BeforeNodeRestoredEvent;
use OCA\Files_Trashbin\Trash\ITrashItem;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IUserSession;

/**
 * @template-implements IEventListener<BeforeNodeRestoredEvent>
 */
class SyncLivePhotosListener implements IEventListener {
	/** @var Array<int, bool> */
	private array $pendingRestores = [];

	public function __construct(
		private ?IUserSession $userSession,
		private ITrashManager $trashManager,
		private LivePhotosService $livePhotosService,
	) {
	}

	public function handle(Event $event): void {
		if ($this->userSession === null) {
			return;
		}

		/** @var BeforeNodeRestoredEvent $event */
		$peerFileId = $this->livePhotosService->getLivePhotoPeerId($event->getSource()->getId());

		if ($peerFileId === null) {
			return; // Not a live photo.
		}

		// Check the user's trashbin.
		$user = $this->userSession->getUser();
		if ($user === null) {
			return;
		}

		$peerFile = $this->trashManager->getTrashNodeById($user, $peerFileId);

		if ($peerFile === null) {
			return; // Peer file not found.
		}

		$this->handleRestore($event, $peerFile);
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
			$user = $this->userSession?->getUser();
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
