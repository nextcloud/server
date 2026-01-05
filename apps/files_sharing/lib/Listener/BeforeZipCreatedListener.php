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
use OCP\Files\Node;
use OCP\IUserSession;

/**
 * @template-implements IEventListener<BeforeZipCreatedEvent|Event>
 */
class BeforeZipCreatedListener implements IEventListener {

	public function __construct(
		private IUserSession $userSession,
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

		// Check whether the user can download the requested folder
		if (!$this->viewOnly->isNodeCanBeDownloaded($event->getFolder())) {
			$event->setSuccessful(false);
			$event->setErrorMessage('Access to this resource has been denied.');
			return;
		}

		// Check recursively whether the user can download nested nodes
		$event->addNodeFilter(fn (Node $node) => $this->viewOnly->isNodeCanBeDownloaded($node));
	}
}
