<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Trashbin\Listener;

use OCA\Files_Trashbin\Storage;
use OCA\Files_Trashbin\Trashbin;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\BeforeFileSystemSetupEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IUserManager;
use OCP\User\Events\BeforeUserDeletedEvent;

/** @template-implements IEventListener<NodeWrittenEvent|BeforeUserDeletedEvent|BeforeFileSystemSetupEvent> */
class EventListener implements IEventListener {
	public function __construct(
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
		private ?string $userId = null,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof NodeWrittenEvent) {
			// Resize trash
			if (empty($this->userId)) {
				return;
			}
			try {
				/** @var Folder $trashRoot */
				$trashRoot = $this->rootFolder->get('/' . $this->userId . '/files_trashbin');
			} catch (NotFoundException|NotPermittedException) {
				return;
			}

			$user = $this->userManager->get($this->userId);
			if ($user) {
				Trashbin::resizeTrash($trashRoot, $user);
			}
		}

		// Clean up user specific settings if user gets deleted
		if ($event instanceof BeforeUserDeletedEvent) {
			Trashbin::deleteUser($event->getUser()->getUID());
		}

		if ($event instanceof BeforeFileSystemSetupEvent) {
			Storage::setupStorage();
		}
	}
}
