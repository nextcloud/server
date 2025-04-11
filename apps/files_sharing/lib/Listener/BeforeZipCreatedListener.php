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

	public function handle(Event $event): void {
		if (!($event instanceof BeforeZipCreatedEvent)) {
			return;
		}

		$dir = $event->getDirectory();
		$files = $event->getFiles();

		$pathsToCheck = [];
		foreach ($files as $file) {
			$pathsToCheck[] = $dir . '/' . $file;
		}

		// Check only for user/group shares. Don't restrict e.g. share links
		$user = $this->userSession->getUser();
		if ($user) {
			$viewOnlyHandler = new ViewOnly(
				$this->rootFolder->getUserFolder($user->getUID())
			);
			if (!$viewOnlyHandler->check($pathsToCheck)) {
				$event->setErrorMessage('Access to this resource or one of its sub-items has been denied.');
				$event->setSuccessful(false);
			} else {
				$event->setSuccessful(true);
			}
		} else {
			$event->setSuccessful(true);
		}
	}
}
