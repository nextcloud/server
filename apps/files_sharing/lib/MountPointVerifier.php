<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing;

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\Share\Events\VerifyMountPointEvent;
use OCP\Share\IShare;
use OCP\Share\IManager;

class MountPointVerifier {
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly IManager $shareManager,
	) {
	}


	/**
	 * Check that there are no conflicts between the share mount and adjust the share target if needed
	 *
	 * @param IUser $user
	 * @param IShare $share
	 * @return void
	 */
	public function verifyMountPoint(IUser $user, IShare $share) {
		$mountName = basename($share->getTarget());
		$parentPath = dirname($share->getTarget());

		$view = new View("/{$user->getUID()}/files");
		$event = new VerifyMountPointEvent($share, $view, $parentPath);
		$this->eventDispatcher->dispatchTyped($event);
		$parentPath = $event->getParent();

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());

		try {
			$parent = $userFolder->get($parentPath);
			if (!$parent instanceof Folder) {
				throw new \Exception("Share parent ($parentPath) is not a folder for user {$user->getUID()}");
			}
		} catch (NotFoundException) {
			$parent = $userFolder->newFolder($parentPath);
		}

		$newMountPoint = $this->generateUniqueTarget(
			$mountName,
			$parent,
			$share,
		);

		if ($newMountPoint !== $share->getTarget()) {
			$share->setTarget($newMountPoint);
			$this->shareManager->moveShare($share, $user->getUID());
		}

		return $newMountPoint;
	}

	private function generateUniqueTarget(string $path, Folder $parentFolder, IShare $share): string {
		$pathInfo = pathinfo($path);
		$ext = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
		$name = $pathInfo['filename'];
		$dir = $pathInfo['dirname'];

		$i = 2;
		while (true) {
			try {
				$node = $parentFolder->get($path);
			} catch (NotFoundException) {
				break;
			}
			$mount = $node->getMountPoint();
			if ($mount instanceof SharedMount) {
				if ($mount->getShare()->getId() === $share->getId()) {
					break;
				}
			}

			$path = Filesystem::normalizePath($dir . '/' . $name . ' (' . $i . ')' . $ext);
			$i++;
		}

		return $path;
	}
}
