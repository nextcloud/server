<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\ViewOnly;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\BeforeZipCreatedEvent;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IUserSession;

/**
 * @template-implements IEventListener<BeforeZipCreatedEvent|Event>
 */
class BeforeZipCreatedListener implements IEventListener {

	public function __construct(
		private IUserSession $userSession,
		private IRootFolder $rootFolder,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof BeforeZipCreatedEvent)) {
			return;
		}

		$folderToCheck = $event->getFolder() ?? $this->getFolderFromEventDirectory($event);
		if ($folderToCheck === null) {
			// there is no way to know if the file is downloadable or not, allow it
			$event->setSuccessful(true);
			return;
		}

		$viewOnlyHandler = new ViewOnly($folderToCheck);
		if (!$viewOnlyHandler->isDownloadable($folderToCheck)) {
			$message = $event->allowsPartialArchive() ? 'Access to this resource and its children has been denied.' : 'Access to this resource or one of its sub-items has been denied.';
			$event->setErrorMessage($message);
			$event->setSuccessful(false);
			return;
		}

		if ($event->allowsPartialArchive()) {
			$event->setSuccessful(true);
			$event->addNodeFilter(fn (Node $node): ?string => $viewOnlyHandler->isDownloadable($node)
				? null
				: 'Download is disabled for this resource');
		} elseif ($viewOnlyHandler->check($event->getFiles())) {
			$event->setSuccessful(true);
		} else {
			// with partial archives disabled: block if any selected item is view-only
			$event->setErrorMessage('Access to this resource or one of its sub-items has been denied.');
			$event->setSuccessful(false);
		}
	}

	/**
	 * Resolves a folder using the path provided by {@see BeforeZipCreatedEvent::getDirectory()},
	 * which is relative to the current user's folder.
	 */
	private function getFolderFromEventDirectory(BeforeZipCreatedEvent $event): ?Folder {
		$user = $this->userSession->getUser();
		if ($user === null) {
			// no user is set, so we can't resolve the path
			return null;
		}

		/** @psalm-suppress DeprecatedMethod path-only API, we need to keep backwards-compatibility */
		$dir = $event->getDirectory();
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());

		try {
			$node = $userFolder->getPath() === $dir ? $userFolder : $userFolder->get($dir);
		} catch (NotFoundException) {
			return null;
		}

		return $node instanceof Folder ? $node : null;
	}
}
