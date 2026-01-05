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
use OCP\Files\Events\BeforeDirectFileDownloadEvent;
use OCP\Files\IRootFolder;
use OCP\IUserSession;

/**
 * @template-implements IEventListener<BeforeDirectFileDownloadEvent|Event>
 */
class BeforeDirectFileDownloadListener implements IEventListener {

	public function __construct(
		private IUserSession $userSession,
		private IRootFolder $rootFolder,
		private ViewOnly $viewOnly,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeDirectFileDownloadEvent)) {
			return;
		}

		$user = $this->userSession->getUser();
		// Check only for user/group shares. Don't restrict e.g. share links
		if (!$user) {
			return;

		}
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$node = $userFolder->get($event->getPath());
		if (!$this->viewOnly->isNodeCanBeDownloaded($node)) {
			$event->setSuccessful(false);
			$event->setErrorMessage('Access to this resource or one of its sub-items has been denied.');
		}
	}
}
