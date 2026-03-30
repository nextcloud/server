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
use OCP\Files\IRootFolder;
use OCP\Files\Node;
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

		/** @psalm-suppress DeprecatedMethod should be migrated to getFolder but for now it would just duplicate code */
		$dir = $event->getDirectory();
		$files = $event->getFiles();

		if (empty($files)) {
			$pathsToCheck = [];
		} else {
			$pathsToCheck = [];
			foreach ($files as $file) {
				$pathsToCheck[] = $file;
			}
		}

		$user = $this->userSession->getUser();
		$folder = $event->getFolder();
		if ($user === null && $event->getFolder() === null) {
			// there is no way to know if the file is downloadable or not, allow it
			$event->setSuccessful(true);
			return;
		}

		// in link-shares there may be no user, in that case we check that the share folder is downloadable
		$userFolder = $user ? $this->rootFolder->getUserFolder($user->getUID()) : null;
		$folderToCheck = $userFolder ? $userFolder->get($dir) : $folder;

		$viewOnlyHandler = new ViewOnly($folderToCheck);
		$isRootDownloadable = $viewOnlyHandler->isDownloadable($folderToCheck);

		if (!$isRootDownloadable) {
			$message = $event->allowPartialArchive ? 'Access to this resource and its children has been denied.' : 'Access to this resource or one of its sub-items has been denied.';
			$event->setErrorMessage($message);
			$event->setSuccessful(false);
			return;
		}

		if ($event->allowPartialArchive) {
			$event->setSuccessful(true);
			$event->addNodeFilter(fn (Node $node): array => [
				$viewOnlyHandler->isDownloadable($node),
				'Download is disabled for this resource'
			]);
		} elseif ($viewOnlyHandler->check($pathsToCheck)) {
			// keep the old behaviour
			$event->setSuccessful(true);
		} else {
			$event->setErrorMessage('Access to this resource or one of its sub-items has been denied.');
			$event->setSuccessful(false);
		}
	}
}
