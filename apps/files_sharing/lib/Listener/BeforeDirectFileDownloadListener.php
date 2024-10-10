<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\Services\ShareAccessService;
use OCA\Files_Sharing\ViewOnly;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\BeforeDirectFileDownloadEvent;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\ISharedStorage;
use OCP\IUserSession;

/**
 * @template-implements IEventListener<BeforeDirectFileDownloadEvent|Event>
 */
class BeforeDirectFileDownloadListener implements IEventListener {

	public function __construct(
		private IUserSession $userSession,
		private IRootFolder $rootFolder,
		private ShareAccessService $accessService,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeDirectFileDownloadEvent)) {
			return;
		}

		$pathsToCheck = [$event->getPath()];
		// Check only for user/group shares. Don't restrict e.g. share links
		$user = $this->userSession->getUser();
		if ($user) {
			$viewOnlyHandler = new ViewOnly(
				$this->rootFolder->getUserFolder($user->getUID())
			);
			if (!$viewOnlyHandler->check($pathsToCheck)) {
				$event->setSuccessful(false);
				$event->setErrorMessage('Access to this resource or one of its sub-items has been denied.');
				return;
			}
		}

		$node = $event->getNode();
		if ($node !== null) {
			$storage = $node->getStorage();
			if ($storage->instanceOfStorage(ISharedStorage::class)) {
				/** @var ISharedStorage $storage */
				$share = $storage->getShare();
				$this->accessService->shareDownloaded($share);
				// All we now need to do is log the download
				$this->accessService->sharedFileDownloaded($share, $node);
			}
		}
	}
}
