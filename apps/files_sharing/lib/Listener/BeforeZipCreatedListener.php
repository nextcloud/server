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
		private ViewOnly $viewOnly,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeZipCreatedEvent)) {
			return;
		}

		$user = $this->userSession->getUser();
		if (!$user) {
			return;
		}

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		// Check whether the user can download the requested folder
		$folder = $userFolder->get(substr($event->getDirectory(), strlen($userFolder->getPath())));
		if (!$this->viewOnly->isNodeCanBeDownloaded($folder)) {
			$event->setSuccessful(false);
			$event->setErrorMessage('Access to this resource has been denied.');
			return;
		}

		$nodes = array_filter($event->getNodes(), fn ($node) => $this->viewOnly->isNodeCanBeDownloaded($node));
		$event->setNodes(array_values($nodes));
		$event->setSuccessful(true);
	}
}
